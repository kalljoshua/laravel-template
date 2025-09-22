<?php

namespace App\Services;

use App\Models\Lead;

class LeadService
{
    public function list()
    {
        return Lead::all();
    }

    public function create(array $data)
    {
        return Lead::create($data);
    }

    public function get($id)
    {
        return Lead::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $lead = Lead::findOrFail($id);
        $lead->update($data);
        return $lead;
    }

    public function delete($id)
    {
        $lead = Lead::findOrFail($id);
        $lead->delete();
    }

    public function uploadCsv($file)
    {
        $count = 0;
        $handle = fopen($file->getRealPath(), 'r');
        
        // Skip header row
        fgetcsv($handle);
        
        while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
            if (count($data) >= 2) {
                Lead::create([
                    'name' => $data[0] ?? '',
                    'phone' => $data[1] ?? '',
                    'email' => $data[2] ?? null,
                    'metadata' => ['source' => 'csv_import'],
                    'opted_out' => false,
                ]);
                $count++;
            }
        }
        
        fclose($handle);
        return $count;
    }
}
