<?php

namespace App\Http\Controllers;

use Yajra\DataTables\Facades\DataTables;
use App\ProductNameCategory;
use Illuminate\Http\Request;
use Excel;
use DB;

class ProductNameCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('category.view') && !auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $category = ProductNameCategory::where('business_id', $business_id)
                ->select(['name', 'row_no', 'id']);

            return Datatables::of($category)
                ->addColumn(
                    'action',
                    ' 
                    @can("category.delete")
                        <button data-href="{{action(\'ProductNameCategoryController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_ProductNameCategory_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    @endcan'
                )
                ->editColumn('name', function ($row) {
                    return $row->name;
                })
                ->removeColumn('id')
                ->removeColumn('parent_id')
                ->rawColumns([2])
                ->make(false);
        }

        return view('ProductNameCategory.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $categories = ProductNameCategory::where('business_id', $business_id)
            ->select(['name', 'row_no', 'id'])
            ->get();
        $parent_categories = [];

        return view('ProductNameCategory.create')
            ->with(compact('parent_categories'));
    }

    public function createCategory()
    {
        if (!auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $categories = ProductNameCategory::where('business_id', $business_id)
            ->select(['name', 'row_no', 'id'])
            ->get();
        $parent_categories = [];

        return view('ProductNameCategory.createQuick')
            ->with(compact('parent_categories'));
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'row_no']);

            $input['business_id'] = $request->session()->get('user.business_id');
            $input['created_by'] = $request->session()->get('user.id');

            $category = ProductNameCategory::create($input);
            $output = [
                'success' => true,
                'data' => $category,
                'msg' => __("category.added_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong") . '--- ' . $e->getMessage()
            ];
        }

        return $output;
    }

    public function addExcell()
    {
        if (!auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        return view('ProductNameCategory.createExcell');
    }

    public function storeExcell(Request $request)
    {
        if (!auth()->user()->can('category.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $path = $request->file('csv_file')->getRealPath();
            $data = array_map('str_getcsv', file($path));
            DB::beginTransaction();
            $r = 0;
            foreach ($data as $key => $objCSV) {
                $r++;
                $input['row_no'] = $r;
                $input['name'] = $objCSV[0];
                $input['business_id'] = $request->session()->get('user.business_id');
                $input['created_by'] = $request->session()->get('user.id');

                $existingCategory = ProductNameCategory::where('name', $input['name'])->first();
                if ($existingCategory !== null) {
                    continue; 
                }
                $category = ProductNameCategory::create($input);
            }
            DB::commit();
            return redirect(action('ProductNameCategoryController@index'));
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong") . '--- ' . "Line:" . $e->getLine() . "Message:" . $e->getMessage()
            ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!auth()->user()->can('category.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $category = ProductNameCategory::where('business_id', $business_id)->find($id);

            $parent_categories = ProductNameCategory::where('business_id', $business_id)
                ->where('id', '!=', $id)
                ->pluck('name', 'id');

            $is_parent = false;


            return view('ProductNameCategory.edit')
                ->with(compact('category'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('category.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $input = $request->only(['name', 'row_no']);
                $business_id = $request->session()->get('user.business_id');

                $category = ProductNameCategory::where('business_id', $business_id)->findOrFail($id);
                $category->name = $input['name'];
                $category->row_no = $input['row_no'];
                if (!empty($request->input('add_as_sub_cat')) &&  $request->input('add_as_sub_cat') == 1 && !empty($request->input('parent_id'))) {
                    $category->parent_id = $request->input('parent_id');
                } else {
                    $category->parent_id = 0;
                }
                $category->save();

                $output = [
                    'success' => true,
                    'msg' => __("category.updated_success")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return $output;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('category.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $category = ProductNameCategory::where('business_id', $business_id)->findOrFail($id);
                $category->delete();

                $output = [
                    'success' => true,
                    'msg' => __("category.deleted_success")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong")
                ];
            }

            return redirect()->back()->with('output', $output);
        }
    }
}
