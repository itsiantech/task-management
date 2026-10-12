<?php

declare(strict_types=1);

namespace App\Repositories\Accounts;

use App\Enums\ServiceType;
use App\Models\Accounts\Service;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ServiceRepository
{
    public function all(): Collection
    {
        return Service::orderBy('type')->orderBy('name')->get();
    }

    public function active(): Collection
    {
        return Service::active()->orderBy('type')->orderBy('name')->get();
    }

    public function byType(ServiceType $type): Collection
    {
        return Service::byType($type)->active()->orderBy('name')->get();
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Service::orderBy('type')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function find(int $id): ?Service
    {
        return Service::find($id);
    }

    public function create(array $data): Service
    {
        return Service::create($data);
    }

    public function update(Service $service, array $data): Service
    {
        $service->update($data);
        return $service->fresh();
    }

    public function delete(Service $service): bool
    {
        // Check if service is used in any invoice items
        if ($service->invoiceItems()->exists()) {
            throw new \Exception('Cannot delete service that is used in invoices.');
        }

        return $service->delete();
    }

    public function toggle(Service $service): Service
    {
        $service->is_active = !$service->is_active;
        $service->save();
        return $service->fresh();
    }

    public function getStatistics(): array
    {
        $services = Service::all();

        return [
            'total' => $services->count(),
            'active' => $services->where('is_active', true)->count(),
            'inactive' => $services->where('is_active', false)->count(),
            'by_type' => [
                'domain' => $services->where('type', ServiceType::DOMAIN->value)->count(),
                'hosting' => $services->where('type', ServiceType::HOSTING->value)->count(),
                'web_development' => $services->where('type', ServiceType::WEB_DEVELOPMENT->value)->count(),
                'digital_marketing' => $services->where('type', ServiceType::DIGITAL_MARKETING->value)->count(),
            ],
        ];
    }
}
