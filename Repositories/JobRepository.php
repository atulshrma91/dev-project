<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class JobRepository
{
    public function createJobApplication($data)
    {
        $id = DB::table('wp_wpjb_application')->insertGetId([
            'job_id' => $data['job_id'],
            'applicant_name' => $data['applicant_name'],
            'email' => $data['email'],
            'status' => $data['status'],
            'applied_at' => $data['applied_at'],
            'message' => isset($data['message']) ? $data['message'] : '',
        ]);


        if (!empty($meta)) {
            $obj = new \stdClass;
            $obj->id = $id;
            $obj->type = 'job';
            $this->saveMeta($meta, $object);
        }

        return $id;
    }

    public function getJobPostingByMeta($name, $value)
    {
        return DB::table('wp_wpjb_meta_value AS mv')
            ->join('wp_wpjb_meta AS m', 'mv.meta_id', '=', 'm.id')
            ->join('wp_wpjb_job AS j', 'mv.object_id', '=', 'j.id')
            ->where('m.name', $name)
            ->where('mv.value', $value)
            ->first();
    }

    public function createJobPosting($data, $meta)
    {
        $employer = DB::table('wp_wpjb_company')
            ->where('user_id', $data['user_id'])
            ->first();

        $id = DB::table('wp_wpjb_job')
            ->insertGetId([
                'employer_id' => $employer->id,
                'job_title' => $data['title'],
                'job_slug' => $data['slug'],
                'job_description' => $data['description'],
                'job_country' => 840,
                'job_city' => '',
                'job_state' => '',
                'job_zip_code' => '',
                'company_name' => $employer->company_name,
                'company_email' => '',
                'company_url' => $employer->company_website,
                'is_approved' => 1,
                'is_active' => 1,
                'is_featured' => 0,
            ]);

        if (!empty($meta)) {
            $obj = new \stdClass;
            $obj->id = $id;
            $obj->type = 'job';
            $this->saveMeta($meta, $obj);
        }

        return $id;
    }

    public function saveMeta($meta, $object)
    {
        $metaNames = DB::table('wp_wpjb_meta')
            ->where('meta_object', $object->type)
            ->where('meta_type', 1)
            ->whereIn('name', array_keys($meta))
            ->get();

        if (!empty($metaNames)) {
            $metaValues = [];
            foreach ($metaNames as $name) {
                $metaValues[] = [
                    'meta_id' => $name->id,
                    'object_id' => $object->id,
                    'value' => $meta[$name->name]
                ];
            }
            DB::table('wp_wpjb_meta_value')
                ->insert($metaValues);
        }
    }
}
