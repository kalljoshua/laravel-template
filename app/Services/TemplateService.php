<?php

namespace App\Services;

use App\Models\Template;

class TemplateService
{
    public function list()
    {
        return Template::all();
    }

    public function create(array $data)
    {
        return Template::create($data);
    }

    public function get($id)
    {
        return Template::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $template = Template::findOrFail($id);
        $template->update($data);
        return $template;
    }

    public function delete($id)
    {
        $template = Template::findOrFail($id);
        $template->delete();
    }
}
