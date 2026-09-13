<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Traits\HasPagePermissions;
use Inertia\Inertia;

class SocialsController extends Controller
{
    public function index()
    {
        $data = [
            // ─── Facebook ─────────────────────────────────────────────
            'facebook' => [
                'metrics' => [
                    'visitors' => 12456,
                    'visitors_trend' => '+12%',
                    'reactions' => 32189,
                    'reactions_trend' => '+8%',
                    'followers' => 24567,
                    'new_followers' => 342,
                ],
                'posts' => [
                    [
                        'id' => 1,
                        'caption' => 'We\'re excited to introduce our new eco‑friendly cotton blend! 🌿 Made from 100% sustainable materials, perfect for your next collection. Visit our showroom to see the samples.',
                        'created_time' => '2026-08-07T10:30:00+08:00',
                        'media_url' => 'https://picsum.photos/seed/cotton/600/400',
                        'reactions' => ['like' => 245, 'love' => 89, 'haha' => 12, 'wow' => 34],
                        'comments_count' => 56,
                        'shares_count' => 23,
                        'permalink' => 'https://facebook.com/montitextile/posts/1',
                    ],
                    [
                        'id' => 2,
                        'caption' => 'Join us this Saturday for a live demo of our new weaving machines! 🧵 See how we create the highest quality fabrics. Register now: https://montitextile.com/event',
                        'created_time' => '2026-08-05T14:15:00+08:00',
                        'media_url' => null,
                        'reactions' => ['like' => 178, 'love' => 45, 'haha' => 8, 'wow' => 22],
                        'comments_count' => 34,
                        'shares_count' => 12,
                        'permalink' => 'https://facebook.com/montitextile/posts/2',
                    ],
                    [
                        'id' => 3,
                        'caption' => 'We’re hiring! 📢 Join the Monti Textile family. We’re looking for talented textile engineers and production managers. Check our careers page for details.',
                        'created_time' => '2026-08-03T09:00:00+08:00',
                        'media_url' => 'https://picsum.photos/seed/hiring/600/400',
                        'reactions' => ['like' => 98, 'love' => 23, 'haha' => 2, 'wow' => 8],
                        'comments_count' => 45,
                        'shares_count' => 67,
                        'permalink' => 'https://facebook.com/montitextile/posts/3',
                    ],
                ],
                'interactions' => [
                    [
                        'id' => 1,
                        'user' => 'Maria Santos',
                        'comment' => 'I’m interested in the cotton blend. Can I get a sample?',
                        'sentiment' => 'Interested',
                        'post_id' => 1,
                        'created_at' => '2026-08-07T11:20:00+08:00',
                    ],
                    [
                        'id' => 2,
                        'user' => 'Carlos Reyes',
                        'comment' => 'What are the pricing options for bulk orders?',
                        'sentiment' => 'Inquiry',
                        'post_id' => 1,
                        'created_at' => '2026-08-07T12:45:00+08:00',
                    ],
                    [
                        'id' => 3,
                        'user' => 'Anna Cruz',
                        'comment' => 'I’ll be there on Saturday!',
                        'sentiment' => 'Positive',
                        'post_id' => 2,
                        'created_at' => '2026-08-06T09:30:00+08:00',
                    ],
                ],
            ],
            // ─── Instagram ─────────────────────────────────────────────
            'instagram' => [
                'metrics' => [
                    'visitors' => 8432,
                    'visitors_trend' => '+5%',
                    'reactions' => 18567,
                    'reactions_trend' => '+15%',
                    'followers' => 18342,
                    'new_followers' => 156,
                ],
                'posts' => [
                    [
                        'id' => 1,
                        'caption' => 'Our new sustainable cotton collection is here! 🌿💚 #MontiTextile #EcoFashion',
                        'created_time' => '2026-08-06T15:20:00+08:00',
                        'media_url' => 'https://picsum.photos/seed/insta1/600/400',
                        'reactions' => ['like' => 412, 'love' => 67, 'haha' => 5, 'wow' => 28],
                        'comments_count' => 28,
                        'shares_count' => 15,
                        'permalink' => 'https://instagram.com/p/123456',
                    ],
                    [
                        'id' => 2,
                        'caption' => 'Behind the scenes at our weaving factory 🏭✨ See how we turn raw yarn into premium fabric.',
                        'created_time' => '2026-08-04T11:45:00+08:00',
                        'media_url' => 'https://picsum.photos/seed/insta2/600/400',
                        'reactions' => ['like' => 324, 'love' => 52, 'haha' => 3, 'wow' => 18],
                        'comments_count' => 19,
                        'shares_count' => 9,
                        'permalink' => 'https://instagram.com/p/789012',
                    ],
                    [
                        'id' => 3,
                        'caption' => 'Meet our team! 🙌 They are the heart of Monti Textile. #TeamMonti',
                        'created_time' => '2026-08-01T08:00:00+08:00',
                        'media_url' => 'https://picsum.photos/seed/insta3/600/400',
                        'reactions' => ['like' => 287, 'love' => 45, 'haha' => 8, 'wow' => 12],
                        'comments_count' => 42,
                        'shares_count' => 31,
                        'permalink' => 'https://instagram.com/p/345678',
                    ],
                ],
                'interactions' => [
                    [
                        'id' => 4,
                        'user' => 'Jose Garcia',
                        'comment' => 'Beautiful collection! Do you ship internationally?',
                        'sentiment' => 'Inquiry',
                        'post_id' => 1,
                        'created_at' => '2026-08-06T16:10:00+08:00',
                    ],
                    [
                        'id' => 5,
                        'user' => 'Liza Lim',
                        'comment' => 'I love this! How can I order?',
                        'sentiment' => 'Interested',
                        'post_id' => 1,
                        'created_at' => '2026-08-06T17:30:00+08:00',
                    ],
                    [
                        'id' => 6,
                        'user' => 'Rosa Martinez',
                        'comment' => 'Great team! 🥰',
                        'sentiment' => 'Positive',
                        'post_id' => 3,
                        'created_at' => '2026-08-02T09:15:00+08:00',
                    ],
                ],
            ],
            // ─── Emails ────────────────────────────────────────────────
            'emails' => [
                'metrics' => [
                    'total' => 24,
                    'unread' => 8,
                    'leads_generated' => 5,
                ],
                'emails' => [
                    [
                        'id' => 1,
                        'subject' => 'Inquiry about cotton blend samples',
                        'from' => 'jane.doe@example.com',
                        'to' => 'info@montitextile.com',
                        'date' => '2026-08-09T09:30:00+08:00',
                        'body' => 'Hi, I am interested in your cotton blend. Can you send me some samples? We are looking to place a large order.',
                        'is_read' => false,
                        'sentiment' => 'Interested',
                    ],
                    [
                        'id' => 2,
                        'subject' => 'Request for pricing – bulk silk',
                        'from' => 'carlos@weave.com',
                        'to' => 'info@montitextile.com',
                        'date' => '2026-08-08T14:15:00+08:00',
                        'body' => 'We need a quote for 5,000 yards of silk fabric. Please include shipping to Manila.',
                        'is_read' => true,
                        'sentiment' => 'Inquiry',
                    ],
                    [
                        'id' => 3,
                        'subject' => 'Thank you for the samples',
                        'from' => 'anna.cruz@moderntextiles.com',
                        'to' => 'info@montitextile.com',
                        'date' => '2026-08-07T11:45:00+08:00',
                        'body' => 'We received the polyester samples. They look great! We will send our feedback soon.',
                        'is_read' => true,
                        'sentiment' => 'Positive',
                    ],
                    [
                        'id' => 4,
                        'subject' => 'Order confirmation – nylon fabric',
                        'from' => 'liza@fashionfwd.com',
                        'to' => 'sales@montitextile.com',
                        'date' => '2026-08-06T08:00:00+08:00',
                        'body' => 'We confirm the order for 8,000 yards of nylon. Please proceed with production.',
                        'is_read' => false,
                        'sentiment' => 'Positive',
                    ],
                    [
                        'id' => 5,
                        'subject' => 'Complaint about delivery delay',
                        'from' => 'rosa@cottonworld.com',
                        'to' => 'support@montitextile.com',
                        'date' => '2026-08-05T16:30:00+08:00',
                        'body' => 'Our shipment arrived 5 days late. This caused a production halt. Please investigate.',
                        'is_read' => false,
                        'sentiment' => 'Feedback',
                    ],
                    [
                        'id' => 6,
                        'subject' => 'New collaboration proposal',
                        'from' => 'miguel.torres@example.com',
                        'to' => 'partnerships@montitextile.com',
                        'date' => '2026-08-04T13:00:00+08:00',
                        'body' => 'We are a sustainable fashion brand. We would like to discuss a long-term partnership with Monti Textile for organic fabrics.',
                        'is_read' => true,
                        'sentiment' => 'Interested',
                    ],
                    [
                        'id' => 7,
                        'subject' => 'Question about fabric care instructions',
                        'from' => 'customers@greenfabrics.com',
                        'to' => 'support@montitextile.com',
                        'date' => '2026-08-03T10:20:00+08:00',
                        'body' => 'Could you please send us the care instructions for the wool blend we purchased? We need it for our labels.',
                        'is_read' => false,
                        'sentiment' => 'Inquiry',
                    ],
                    [
                        'id' => 8,
                        'subject' => 'Request for quote – large volume',
                        'from' => 'procurement@fashionfwd.com',
                        'to' => 'sales@montitextile.com',
                        'date' => '2026-08-02T09:45:00+08:00',
                        'body' => 'We are interested in your premium cotton. Please provide a quote for 15,000 yards.',
                        'is_read' => true,
                        'sentiment' => 'Inquiry',
                    ],
                ],
                'interactions' => [
                    [
                        'id' => 101,
                        'user' => 'Jane Doe',
                        'comment' => 'Lead created from email inquiry (cotton samples)',
                        'sentiment' => 'Interested',
                        'created_at' => '2026-08-09T10:00:00+08:00',
                    ],
                    [
                        'id' => 102,
                        'user' => 'Carlos Reyes',
                        'comment' => 'Lead created from pricing request',
                        'sentiment' => 'Inquiry',
                        'created_at' => '2026-08-08T15:00:00+08:00',
                    ],
                    [
                        'id' => 103,
                        'user' => 'Miguel Torres',
                        'comment' => 'Lead created from partnership proposal',
                        'sentiment' => 'Interested',
                        'created_at' => '2026-08-04T14:30:00+08:00',
                    ],
                    [
                        'id' => 104,
                        'user' => 'Procurement Team',
                        'comment' => 'Lead created from large volume quote request',
                        'sentiment' => 'Inquiry',
                        'created_at' => '2026-08-02T10:15:00+08:00',
                    ],
                ],
            ],
        ];

        $permissions = collect(['leads' => 'edit']);

        return Inertia::render('Dashboard/CRM/Socials', [
            'data' => $data,
            'permissions' => $permissions,
        ]);
    }

    // ─── Legacy Methods (unchanged) ────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'interest_fabric' => 'required|string',
            'estimated_value' => 'required|numeric|min:0',
        ]);
        return back()->with('message', 'New lead created (demo).');
    }

    public function updateStatus(Request $request, $id)
    {
        return back();
    }

    public function convertToClient(Request $request)
    {
        $request->validate([
            'lead_id' => 'required|exists:crm_leads,id',
            'company_name' => 'required|string|max:255',
            'business_type' => 'required|string',
            'tin_number' => 'required|string|unique:clients,tin_number',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'required|string',
            'company_address' => 'required|string',
            'password' => 'required|string|min:8',
        ]);
        return back()->with('message', 'Lead converted (demo).');
    }

    public function addNote(Request $request, $id)
    {
        $request->validate(['note' => 'required|string|max:2000']);
        return back()->with('message', 'Note added (demo).');
    }

    public function scheduleInterview(Request $request, $id)
    {
        $request->validate([
            'scheduled_at' => 'required|date',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);
        return back()->with('message', 'Interview scheduled (demo).');
    }

    public function uploadApprovalFile(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);
        return back()->with('message', 'File uploaded (demo).');
    }

    public function acceptLead($id)
    {
        return back()->with('message', 'Lead accepted (demo).');
    }

    public function rejectLead(Request $request, $id)
    {
        $request->validate(['reject_reason' => 'required|string|max:255']);
        return back()->with('message', 'Lead rejected (demo).');
    }
}
