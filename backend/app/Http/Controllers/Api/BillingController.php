<?php

namespace App\Http\Controllers\Api;

use App\Data\BillingSummaryData;
use App\Data\PlanData;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Plan;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Spatie\LaravelData\DataCollection;

class BillingController extends Controller
{
    public function plans(): DataCollection
    {
        $plans = Plan::query()->orderBy('price_monthly')->get()
            ->map(fn (Plan $plan) => PlanData::fromModel($plan))
            ->all();

        return new DataCollection(PlanData::class, $plans);
    }

    public function summary(Organization $organization, ProjectRepositoryInterface $projects): BillingSummaryData
    {
        $this->authorize('view', $organization);

        // The effective plan: the organization's plan, or the free plan.
        $plan = $organization->plan ?? Plan::query()->where('code', Plan::FREE)->firstOrFail();

        return new BillingSummaryData(
            plan: PlanData::fromModel($plan),
            projectCount: $projects->activeCountForOrganization($organization),
        );
    }
}
