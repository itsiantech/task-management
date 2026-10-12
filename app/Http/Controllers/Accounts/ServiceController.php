<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounts\ServiceRequest;
use App\Models\Accounts\Service;
use App\Services\Accounts\ServiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(
        private readonly ServiceService $service
    ) {}

    public function index(Request $request): View
    {
        $search = $request->query('search');
        $type = $request->query('type');

        $query = Service::query();

        if ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        }

        if ($type) {
            $query->where('type', $type);
        }

        $services = $query->orderBy('type')->orderBy('name')->paginate(15);
        $statistics = $this->service->getStatistics();

        return view('accounts.services.index', compact('services', 'statistics'));
    }

    public function create(): View
    {
        return view('accounts.services.create');
    }

    public function store(ServiceRequest $request): RedirectResponse|JsonResponse
    {
        $service = $this->service->createService($request);

        // Inline "New Service" modal on the invoice form posts JSON.
        if ($request->wantsJson()) {
            return response()->json(['service' => [
                'id' => $service->id,
                'name' => $service->name,
                'type' => $service->type,
                'type_label' => $service->type_label,
                'default_price' => (float) $service->default_price,
            ]], 201);
        }

        return redirect()
            ->route('accounts.services.index')
            ->with('success', 'Service created successfully.');
    }

    public function show(Service $service): View
    {
        $service->load('invoiceItems.invoice.client');

        return view('accounts.services.show', compact('service'));
    }

    public function edit(Service $service): View
    {
        return view('accounts.services.edit', compact('service'));
    }

    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $this->service->updateService($service, $request);

        return redirect()
            ->route('accounts.services.index')
            ->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        try {
            $this->service->deleteService($service);
            return redirect()
                ->route('accounts.services.index')
                ->with('success', 'Service deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('accounts.services.index')
                ->with('error', $e->getMessage());
        }
    }

    public function toggle(Service $service): RedirectResponse
    {
        $this->service->toggleService($service);

        $status = $service->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('accounts.services.index')
            ->with('success', "Service {$status} successfully.");
    }
}
