<?php

namespace App\Http\Controllers;

use App\Size;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    function getSubSize($sizeId)
    {
        $output = [];
        try {
            $business_id = request()->session()->get('user.business_id');
            $output['success'] = true;
            $attributes = ['parent_id' => $sizeId];
            $objGiftCards = Size::where('business_id', $business_id)
                ->where(function ($query) use ($attributes) {
                    foreach ($attributes as $key => $value) {
                        //you can use orWhere the first time, dosn't need to be ->where
                        $query->orWhere($key, $value);
                    }
                })
                ->get();

            if (!empty($objGiftCards)) {
                $output['msg'] = $objGiftCards;
            } else {
                $output['success'] = false;
                $output['msg'] = "Sorry No Data Found ";
            }
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output['success'] = false;
            $output['msg'] = __('gift.error') . " \n " . $e->getMessage();
        }

        return $output;;
    }
    public function index()
    {
        if (!auth()->user()->can('size.view') && !auth()->user()->can('size.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {

            $business_id = request()->session()->get('user.business_id');
            $size = Size::where('business_id', $business_id)->select(['name', 'short_code', 'id', 'parent_id']);

            return Datatables::of($size)
                ->addColumn(
                    'action',
                    '@can("size.update")
                    <button data-href="{{action(\'SizeController@edit\', [$id])}}"  class="btn btn-xs btn-primary btn-modal" data-container=".size_modal"><i class="glyphicon glyphicon-edit"></i>  @lang("messages.edit")</button>
                        &nbsp;
                    @endcan
                    @can("size.delete")
                        <button data-href="{{action(\'SizeController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_category_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    @endcan'
                )
                ->editColumn('name', function ($row) {
                    if ($row->parent_id != 0) {
                        return '--' . $row->name;
                    } else {
                        return $row->name;
                    }
                })
                ->removeColumn('id')
                ->removeColumn('parent_id')
                ->rawColumns([2])
                ->make(false);
        }

        return view('size.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('size.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $sizes = Size::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->select(['name', 'short_code', 'id'])
            ->get();
        $parent_sizes = [];
        if (!empty($sizes)) {
            foreach ($sizes as $size) {
                $parent_sizes[$size->id] = $size->name;
            }
        }
        return view('size.create')
            ->with(compact('parent_sizes'));
    }

    public function createCategory()
    {
        if (!auth()->user()->can('size.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $sizes = Size::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->select(['name', 'short_code', 'id'])
            ->get();
        $parent_sizes = [];

        return view('size.createQuick')
            ->with(compact('parent_sizes'));
    }

    public function createSubCategory()
    {
        if (!auth()->user()->can('size.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $sizes = Size::where('business_id', $business_id)
            ->where('parent_id', 0)
            ->select(['name', 'short_code', 'id'])
            ->get();
        $parent_sizes = [];
        if (!empty($sizes)) {
            foreach ($sizes as $size) {
                $parent_sizes[$size->id] = $size->name;
            }
        }
        return view('size.createQuick')
            ->with(compact('parent_sizes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('size.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'short_code']);
            if (!empty($request->input('add_as_sub_cat')) &&  $request->input('add_as_sub_cat') == 1 && !empty($request->input('parent_id'))) {
                $input['parent_id'] = $request->input('parent_id');
            } else {
                $input['parent_id'] = 0;
            }
            $input['business_id'] = $request->session()->get('user.business_id');
            $input['created_by'] = $request->session()->get('user.id');

            $size = Size::create($input);
            $output = [
                'success' => true,
                'data' => $size,
                'msg' => __("size.added_success")
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
        if (!auth()->user()->can('size.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $size = Size::where('business_id', $business_id)->find($id);

            $parent_sizes = Size::where('business_id', $business_id)
                ->where('parent_id', 0)
                ->where('id', '!=', $id)
                ->pluck('name', 'id');

            $is_parent = false;

            if ($size->parent_id == 0) {
                $is_parent = true;
                $selected_parent = null;
            } else {
                $selected_parent = $size->parent_id;
            }

            return view('size.edit')
                ->with(compact('size', 'parent_sizes', 'is_parent', 'selected_parent'));
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
        if (!auth()->user()->can('size.update')) {
            abort(403, 'Unauthorized action.');
        }

        // dd($request->input());
        if ($request->input('name')) {
            try {
                $input = $request->only(['name', 'short_code']);
                $business_id = $request->session()->get('user.business_id');

                $size = Size::where('business_id', $business_id)->findOrFail($id);
                $size->name = $input['name'];
                $size->short_code = $input['short_code'];
                if (!empty($request->input('add_as_sub_cat')) &&  $request->input('add_as_sub_cat') == 1 && !empty($request->input('parent_id'))) {
                    $size->parent_id = $request->input('parent_id');
                } else {
                    $size->parent_id = 0;
                }
                $size->save();

                $output = [
                    'success' => true,
                    'msg' => __("size.updated_success")
                ];
                return redirect()->back()->with(['output' => $output]);
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
        if (!auth()->user()->can('size.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $size = Size::where('business_id', $business_id)->findOrFail($id);
                $size->delete();

                $output = [
                    'success' => true,
                    'msg' => __("size.deleted_success")
                ];
            } catch (\Exception $e) {
                \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __("messages.something_went_wrong") . $e->getMessage()
                ];
            }

            return $output;
        }
    }
}
