<?php

declare(strict_types=1);

namespace App\Services\Accounts;

use App\Http\Requests\Accounts\ServiceRequest;
use App\Models\Accounts\Service;
use App\Repositories\Accounts\ServiceRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceService
{
    public function __construct(
        private readonly ServiceRepository $repository
    ) {}

    public function getAllServices(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->all();
    }

    public function getActiveServices(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->repository->active();
    }

    public function getPaginatedServices(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function getServiceById(int $id): ?Service
    {
        return $this->repository->find($id);
    }

    public function createService(ServiceRequest $request): Service
    {
        return $this->repository->create($request->validated());
    }

    public function updateService(Service $service, ServiceRequest $request): Service
    {
        return $this->repository->update($service, $request->validated());
    }

    public function deleteService(Service $service): bool
    {
        return $this->repository->delete($service);
    }

    public function toggleService(Service $service): Service
    {
        return $this->repository->toggle($service);
    }

    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    public function getServiceReportData(): array
    {
        $services = $this->repository->active();

        $report = [];
        foreach ($services as $service) {
            $totalBilled = $service->invoiceItems()->sum('line_total');
            $totalQty = $service->invoiceItems()->sum('qty');

            $report[] = [
                'id' => $service->id,
                'name' => $service->name,
                'type' => $service->type,
                'type_label' => $service->type_label,
                'default_price' => $service->default_price,
                'total_billed' => $totalBilled,
                'total_qty' => $totalQty,
                'invoice_count' => $service->invoiceItems()->distinct('invoice_id')->count('invoice_id'),
            ];
        }

        return $report;
    }
}
