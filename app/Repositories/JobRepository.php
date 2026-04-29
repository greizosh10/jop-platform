<?php
namespace App\Repositories;
use App\Models\Job;

class JobRepository {
    public function getAll() {
        return Job::all();
    }
    public function create(array $data) {
    return \App\Models\Job::create($data);
}
}
