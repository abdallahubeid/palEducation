<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Models\NewsletterCampaign;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The record of what has been sent.
 *
 * 🔴 Read and delete only. There is no edit and no resend-in-place: the
 * message is already in other people's inboxes, so a screen that changed it
 * afterwards would show a history that never happened. Sending again creates
 * a NEW row, which is what "نسخ كحملة جديدة" does.
 */
class NewsletterCampaignHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), array_keys(CampaignStatus::options()), true)
            ? $request->query('status')
            : 'all';

        return view('admin.newsletter.campaigns.index', [
            'campaigns' => NewsletterCampaign::query()
                ->when($status !== 'all', fn ($query) => $query->where('status', $status))
                /*
                 * ⚠️ admin.userable, not admin. displayName() reads the domain
                 * row behind the account, so eager-loading only the user still
                 * leaves one query per row — an N+1 that grows with the
                 * history for ever.
                 */
                ->with('admin.userable')
                ->latestFirst()
                ->paginate(config('app.paginate_page'))
                ->withQueryString(),

            'activeStatus' => $status,

            // Counted unfiltered, so the tabs describe the whole history
            // while looking at one slice of it.
            'counts' => [
                'all' => NewsletterCampaign::query()->count(),
                'processing' => NewsletterCampaign::query()->where('status', CampaignStatus::Processing)->count(),
                'completed' => NewsletterCampaign::query()->where('status', CampaignStatus::Completed)->count(),
                'failed' => NewsletterCampaign::query()->where('status', CampaignStatus::Failed)->count(),
            ],
        ]);
    }

    public function destroy(NewsletterCampaign $campaign): RedirectResponse
    {
        $campaign->delete();

        flash()->warning('تم حذف العنصر بنجاح.');

        // Undo is a real PATCH form with CSRF in the shell, never a link in
        // the notice: a restore mutates server state.
        session()->flash('admin.undo', [
            'url' => route('admin.trash.restore', ['type' => 'campaigns', 'id' => $campaign->id]),
            'label' => 'تراجع',
        ]);

        return redirect()->route('admin.newsletter.campaigns.index');
    }
}
