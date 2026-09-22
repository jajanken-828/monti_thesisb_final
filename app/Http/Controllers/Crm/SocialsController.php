<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmLead;
use App\Models\Crm\CrmSocialAccount;
use App\Traits\HasPagePermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class SocialsController extends Controller
{
    protected const GRAPH = 'https://graph.facebook.com/v19.0';

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

        // Live Facebook page feed (replaces demo posts when connected).
        $fbAccount = CrmSocialAccount::where('platform', 'facebook')->latest()->first();
        $fbError = null;
        if ($fbAccount) {
            try {
                $data['facebook']['posts'] = $this->fetchPosts($fbAccount);
                $fbAccount->update(['last_synced_at' => now(), 'last_error' => null]);
            } catch (\Exception $e) {
                $fbError = $e->getMessage();
                $fbAccount->update(['last_error' => $fbError]);
            }
        }

        return Inertia::render('Dashboard/CRM/Socials', [
            'data' => $data,
            'permissions' => $permissions,
            'fbAccount' => $fbAccount ? [
                'page_name' => $fbAccount->page_name,
                'page_url' => $fbAccount->page_url,
                'connected' => true,
                'last_synced_at' => $fbAccount->last_synced_at,
            ] : ['connected' => false],
            'fbError' => $fbError,
            'convertedExternalIds' => CrmLead::where('source', 'facebook')
                ->whereNotNull('external_id')->pluck('external_id')->all(),
        ]);
    }

    // ─── Live Facebook Page integration ───────────────────────────────
    // Meta allows NO unauthenticated page-feed access: a Page access token
    // (Facebook App → your Page) is required. The token is stored encrypted
    // and every Graph call runs server-side so it never leaks to browsers.

    protected function graph(string $path, string $token, array $params = []): array
    {
        $res = Http::timeout(12)->get(self::GRAPH.'/'.ltrim($path, '/'), [
            ...$params, 'access_token' => $token,
        ]);

        if (! $res->successful()) {
            $msg = $res->json('error.message') ?? 'Facebook request failed (HTTP '.$res->status().')';
            throw new \RuntimeException($msg);
        }

        return $res->json();
    }

    /**
     * Accept a full page URL (facebook.com/acme, /pages/Acme/123…, profile
     * .php?id=123) or a bare Page ID / username and return the Graph ref.
     */
    protected function extractPageRef(string $input): string
    {
        $input = trim($input);
        if (preg_match('/profile\.php\?id=(\d+)/i', $input, $m)) {
            return $m[1];
        }
        if (preg_match('#/pages/[^/]+/(\d+)#i', $input, $m)) {
            return $m[1];
        }
        if (preg_match('#facebook\.com/([A-Za-z0-9.\-]+)/?(\?.*)?$#i', $input, $m)) {
            return $m[1];
        }

        return $input; // bare Page ID or username
    }

    public function connectFb(Request $request)
    {
        $data = $request->validate([
            'page_input' => 'required|string|max:255',
            'access_token' => 'required|string|max:2000',
        ]);

        $ref = $this->extractPageRef($data['page_input']);

        try {
            $page = $this->graph($ref, $data['access_token'], ['fields' => 'id,name,link']);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Facebook rejected the connection: '.$e->getMessage()]);
        }

        CrmSocialAccount::updateOrCreate(
            ['platform' => 'facebook'],
            [
                'page_id' => $page['id'],
                'page_name' => $page['name'] ?? null,
                'page_url' => $page['link'] ?? $data['page_input'],
                'access_token' => $data['access_token'],
                'connected_by' => Auth::id(),
                'last_error' => null,
            ]
        );

        return back()->with('message', 'Facebook page connected: '.($page['name'] ?? $page['id']));
    }

    public function disconnectFb()
    {
        CrmSocialAccount::where('platform', 'facebook')->delete();

        return back()->with('message', 'Facebook page disconnected. The demo feed returns until you reconnect.');
    }

    protected function fetchPosts(CrmSocialAccount $account): array
    {
        $feed = $this->graph($account->page_id.'/posts', $account->access_token, [
            'fields' => 'id,message,created_time,permalink_url,full_picture,shares,likes.summary(true),comments.summary(true)',
            'limit' => 15,
        ]);

        $converted = CrmLead::where('source', 'facebook')->whereNotNull('external_id')->pluck('external_id')->all();

        return collect($feed['data'] ?? [])->map(fn ($p) => [
            'id' => $p['id'],
            'caption' => $p['message'] ?? '(no text)',
            'created_time' => $p['created_at'] ?? $p['created_time'] ?? null,
            'media_url' => $p['full_picture'] ?? null,
            'reactions' => ['like' => $p['likes']['summary']['total_count'] ?? 0],
            'comments_count' => $p['comments']['summary']['total_count'] ?? 0,
            'shares_count' => isset($p['shares']['count']) ? (int) $p['shares']['count'] : 0,
            'permalink' => $p['permalink_url'] ?? null,
            'converted' => in_array($p['id'], $converted, true),
            'live' => true,
        ])->values()->all();
    }

    /**
     * Best-effort comment fetch per post (needs pages_read_engagement on
     * the token). Failures return [] — posts still convert without names.
     */
    public function fbComments(Request $request)
    {
        $data = $request->validate(['post_id' => 'required|string|max:128']);
        $account = CrmSocialAccount::where('platform', 'facebook')->first();
        abort_unless($account, 404, 'No Facebook page connected.');

        try {
            $res = $this->graph($data['post_id'].'/comments', $account->access_token, [
                'fields' => 'id,from{id,name},message,created_time',
                'limit' => 25,
            ]);
        } catch (\Exception $e) {
            return response()->json(['comments' => [], 'error' => $e->getMessage()]);
        }

        $converted = CrmLead::where('source', 'facebook')->whereNotNull('external_id')->pluck('external_id')->all();

        return response()->json(['comments' => collect($res['data'] ?? [])->map(fn ($c) => [
            'id' => $c['id'],
            'user' => $c['from']['name'] ?? 'Facebook user',
            'comment' => $c['message'] ?? '',
            'created_at' => $c['created_time'] ?? null,
            'converted' => in_array($c['id'], $converted, true),
        ])->values()->all()]);
    }

    protected function makeLeadFromSocial(array $attrs): CrmLead
    {
        if (CrmLead::where('source', 'facebook')->where('external_id', $attrs['external_id'])->exists()) {
            throw new \RuntimeException('Already converted to a lead.');
        }

        $lead = CrmLead::create([
            'company_name' => $attrs['company_name'],
            'contact_person' => $attrs['contact_person'],
            'email' => $attrs['email'],
            'phone' => 'N/A',
            'need_summary' => $attrs['need_summary'],
            'source' => 'facebook',
            'external_id' => $attrs['external_id'],
            'status' => 'Inquiry',
            'assigned_staff_id' => Auth::id(),
        ]);

        $lead->contacts()->create([
            'name' => $attrs['contact_person'],
            'email' => $attrs['email'],
            'is_decision_maker' => true,
            'is_primary' => true,
            'notes' => 'From Facebook.',
        ]);

        return $lead;
    }

    public function convertPost(Request $request)
    {
        $data = $request->validate(['post_id' => 'required|string|max:128']);
        $account = CrmSocialAccount::where('platform', 'facebook')->first();
        abort_unless($account, 404, 'No Facebook page connected.');

        try {
            $post = $this->graph($data['post_id'], $account->access_token, [
                'fields' => 'id,message,created_time,permalink_url',
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Could not read that post: '.$e->getMessage()]);
        }

        $text = trim((string) ($post['message'] ?? ''));
        $excerpt = mb_substr($text !== '' ? $text : 'Facebook post', 0, 120);

        try {
            $lead = $this->makeLeadFromSocial([
                'company_name' => mb_substr('FB · '.($account->page_name ?? 'Page').' · '.$excerpt, 0, 200),
                'contact_person' => 'Facebook inquiry',
                'email' => 'fb-'.preg_replace('/[^A-Za-z0-9]/', '', $data['post_id']).'@social.local',
                'need_summary' => ($text !== '' ? $text : '(no post text)')."\n— {$post['permalink_url']}",
                'external_id' => $data['post_id'],
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('message', "Lead #{$lead->id} created from Facebook post. Complete the company details on the lead.");
    }

    public function convertComment(Request $request)
    {
        $data = $request->validate([
            'comment_id' => 'required|string|max:128',
            'name' => 'required|string|max:255',
            'message' => 'nullable|string|max:2000',
            'post_id' => 'nullable|string|max:128',
        ]);

        try {
            $lead = $this->makeLeadFromSocial([
                'company_name' => mb_substr($data['name']."'s inquiry", 0, 200),
                'contact_person' => $data['name'],
                'email' => 'fb-'.preg_replace('/[^A-Za-z0-9]/', '', $data['comment_id']).'@social.local',
                'need_summary' => ($data['message'] !== '' ? $data['message'] : '(no comment text)').($data['post_id'] ? "\nOn post {$data['post_id']}" : ''),
                'external_id' => $data['comment_id'],
            ]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('message', "Lead #{$lead->id} created from {$data['name']}.");
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
