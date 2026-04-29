<?php
namespace App\Services;
use App\Repositories\JobRepository;

class JobService {
    protected $repo;
    public function __construct(JobRepository $repo) {
        $this->repo = $repo;
    }

    public function handleListingJobs() {
        // هنا يمكنك إضافة منطق إضافي مستقبلاً (مثل الفلترة)
        return $this->repo->getAll();
    }public function createJob(array $data) {
    // هنا مستقبلاً يمكنك إضافة شروط معينة قبل الحفظ
    return $this->repo->create($data);
}
}
