<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Eco\ConversationAttachment;
use App\Models\Eco\ConversationMessage;
use App\Models\Eco\Inquiry;
use App\Models\Eco\EcoQuotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ClientConversationController extends Controller
{
    public function index()
    {
        $inquiries = Inquiry::where('client_id', Auth::guard('client')->id())
            ->with('product')
            ->latest()
            ->get();

        return Inertia::render('Client/ConversationList', ['inquiries' => $inquiries]);
    }

    public function show(Inquiry $inquiry)
    {
        $this->authorizeClient($inquiry);

        $inquiry->load(['messages' => function ($query) {
            $query->with('attachments')->oldest();
        }, 'product']);

        $quotations = EcoQuotation::with('items')
            ->where('inquiry_id', $inquiry->id)
            ->latest()
            ->get();

        return Inertia::render('Client/ConversationShow', [
            'inquiry' => $inquiry,
            'quotations' => $quotations,
        ]);
    }

    public function sendMessage(Request $request, Inquiry $inquiry)
    {
        $this->authorizeClient($inquiry);

        $request->validate([
            'message' => 'required_without:files|nullable|string',
            'files.*' => 'nullable|file|max:10240',
        ]);

        $message = ConversationMessage::create([
            'inquiry_id' => $inquiry->id,
            'sender_type' => 'client',
            'message' => $request->message ?? '',
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('eco_attachments', 'public');
                ConversationAttachment::create([
                    'conversation_message_id' => $message->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                ]);
            }
        }

        $inquiry->update(['last_message_at' => now()]);

        return back()->with('success', 'Message sent.');
    }

    /**
     * Send one or more Purchase Order files to the conversation.
     */
    public function sendPO(Request $request, Inquiry $inquiry)
    {
        $this->authorizeClient($inquiry);

        $request->validate([
            'po_files' => 'required|array|min:1',
            'po_files.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'notes' => 'nullable|string|max:500',
        ]);

        $message = ConversationMessage::create([
            'inquiry_id' => $inquiry->id,
            'sender_type' => 'client',
            'message' => "📄 **Purchase Order uploaded**\n".($request->notes ? 'Notes: '.$request->notes : ''),
            'is_system_event' => true,
        ]);

        foreach ($request->file('po_files') as $file) {
            $path = $file->store('client_po', 'public');
            ConversationAttachment::create([
                'conversation_message_id' => $message->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_type' => $file->getMimeType(),
                'is_po' => true, // Mark as Purchase Order
            ]);
        }

        $inquiry->update(['last_message_at' => now()]);

        return back()->with('success', 'Purchase Order(s) sent.');
    }

    /**
     * Approve an attachment sent by ECO (or any attachment in the conversation).
     * This is called by the client when they approve a file.
     */
    public function approveAttachment(ConversationAttachment $attachment)
    {
        $inquiry = $attachment->message->inquiry;
        $this->authorizeClient($inquiry);

        $attachment->update(['approved_by_client' => true]);

        // Create a system message to notify ECO
        ConversationMessage::create([
            'inquiry_id' => $inquiry->id,
            'sender_type' => 'client',
            'message' => "✅ Client approved attachment: {$attachment->file_name}",
            'is_system_event' => true,
        ]);

        return back()->with('success', 'Attachment approved.');
    }

    /**
     * Accept a quotation (price agreement only, no order created yet).
     */
    public function acceptQuotation(Request $request, EcoQuotation $quotation)
    {
        // Only allow if the quotation belongs to this client and is still 'sent'
        if ($quotation->client_id !== Auth::guard('client')->id() || $quotation->status !== 'sent') {
            abort(403);
        }

        // Update quotation status
        $quotation->update(['status' => 'accepted']);

        // System message
        $messageText = "Quotation {$quotation->quotation_number} has been ACCEPTED by the client.";
        if ($request->notes) {
            $messageText .= "\n\nNotes from client: " . $request->notes;
        }

        $message = ConversationMessage::create([
            'inquiry_id' => $quotation->inquiry_id,
            'sender_type' => 'client',
            'message' => $messageText,
            'is_system_event' => true,
        ]);

        // Handle any attached files (e.g., proof of payment)
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('eco_attachments', 'public');
                ConversationAttachment::create([
                    'conversation_message_id' => $message->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                ]);
            }
        }

        return back()->with('success', 'Quotation accepted successfully.');
    }

    public function rejectQuotation(Request $request, EcoQuotation $quotation)
    {
        if ($quotation->client_id !== Auth::guard('client')->id() || $quotation->status !== 'sent') {
            abort(403);
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
            'request_new' => 'boolean'
        ]);

        $quotation->update([
            'status' => 'rejected',
            'reject_reason' => $request->reason,
            'request_new_quote' => $request->request_new ?? false,
        ]);

        $msg = "Quotation {$quotation->quotation_number} REJECTED. Reason: {$request->reason}";
        if ($request->request_new) {
            $msg .= " | Client requested a revised quotation.";
        }

        ConversationMessage::create([
            'inquiry_id' => $quotation->inquiry_id,
            'sender_type' => 'client',
            'message' => $msg,
            'is_system_event' => true,
        ]);

        return back()->with('success', 'Quotation rejected.');
    }

    /**
     * Download an accepted quotation as a PDF.
     */
    public function downloadQuotation(EcoQuotation $quotation)
    {
        if ($quotation->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }

        if ($quotation->status !== 'accepted') {
            abort(403, 'Only accepted quotations can be downloaded.');
        }

        $quotation->load(['items', 'inquiry.product']);

        return response()->json([
            'quotation' => $quotation,
            'generated_at' => now()->toDateTimeString(),
        ]);
    }

    public function destroyAttachment(ConversationAttachment $attachment)
    {
        if ($attachment->message->inquiry->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'File removed.');
    }

    private function authorizeClient(Inquiry $inquiry)
    {
        if ($inquiry->client_id !== Auth::guard('client')->id()) {
            abort(403);
        }
    }
}