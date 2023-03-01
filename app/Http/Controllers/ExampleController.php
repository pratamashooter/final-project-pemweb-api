<?php

namespace App\Http\Controllers;

use App\Models\Example;
use Illuminate\Validation\Rule;

class ExampleController extends Controller
{
    public function list()
    {
        $reqSearch = $this->request->input('search');
        $reqIncludes = $this->request->input('includes');
        $reqFilterColumnA = $this->request->input('filter.column_a');
        $reqFilterColumnB = $this->request->input('filter.column_b');

        $this->validate($this->request, [
            'includes' => 'nullable|array',
            'includes.*' => ['required', Rule::in(['relation_a', 'relation_b'])],
            'filter.column_a' => 'nullable|exists:table_a,column_a',
            'filter.column_b' => 'nullable|exists:table_b,column_b',
        ]);

        $query = Example::query();
        if ($reqFilterColumnA !== null) {
            $query->where('column_a', $reqFilterColumnA);
        }
        if ($reqFilterColumnB !== null) {
            $query->where('column_b', $reqFilterColumnB);
        }
        if ($reqSearch !== null) {
            // * SEARCH FOR SINGLE COLUMN
            $query->where(fn ($query) => $query->orWhere('column_a', 'like', "%{$reqSearch}%"));

            // * SEARCH FOR MULTIPLE COLUMN
            // $query->where(function($query) use ($reqSearch) {
            //     $query->orWhere('column_a', 'like', "%{$reqSearch}%")
            //     ->orWhere('column_b', 'like', "%{$reqSearch}%");
            // });
        }
        
        if ($reqIncludes !== null) {
            $query->with($reqIncludes);
        }
        $examples = $query->paginate($this->perPage());

        return $this->respondSuccess($examples, 'Data Loaded Successfully');
    }

    public function detail($id)
    {
        $example = Example::with('relation_a')->findOrFail($id);

        return $this->respondSuccess($example, 'Data Loaded Successfully');
    }

    public function create()
    {
        $reqColumnA = $this->request->input('column_a');
        $reqColumnB = $this->request->input('column_b');

        $this->validate($this->request, [
            'column_a' => 'required',
            'column_b' => 'required',
        ]);

        $example = new Example;
        $example->column_a = $reqColumnA;
        $example->column_b = $reqColumnB;
        $example->save();

        $example = Example::with('relation_a')->findOrFail($example->id);

        return $this->respondSuccess($example, 'Create Success');
    }

    public function update($id)
    {
        $example = Example::findOrFail($id);

        $reqColumnA = $this->request->input('column_a');
        $reqColumnB = $this->request->input('column_b');

        $this->validate($this->request, [
            'column_a' => 'nullable',
            'column_b' => 'nullable',
        ]);

        $example->column_a = $reqColumnA;
        $example->column_b = $reqColumnB;
        $example->save();

        $example = Example::with('relation_a')->findOrFail($id);

        return $this->respondSuccess($example, 'Update Success');
    }

    public function delete($id)
    {
        Example::destroy($id);

        return $this->respondSuccess(null, 'Delete Success');
    }
}
