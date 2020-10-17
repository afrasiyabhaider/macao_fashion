<?php

namespace App\Http\Controllers;

use App\Color;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ColorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('color.view') && !auth()->user()->can('color.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $colors = Color::where('business_id', $business_id)
                ->select(['name', 'color_code', 'description', 'id']);
            // print_r($colors);die();

            return DataTables::of($colors)
                ->addColumn(
                    'action',
                    '@can("color.update")
                    <button data-href="{{action(\'ColorController@edit\', [$id])}}" class="btn btn-xs btn-primary btn-modal" data-container=".colors_modal"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                        &nbsp;
                    @endcan
                    @can("color.delete")
                        <button data-href="{{action(\'ColorController@destroy\', [$id])}}" class="btn btn-xs btn-danger delete_color_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>
                    @endcan'
                )
                ->removeColumn('id', 'description')
                ->rawColumns([2])
                ->make(false);
        }

        return view('color.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!auth()->user()->can('color.create')) {
            abort(403, 'Unauthorized action.');
        }

        $quick_add = false;
        if (!empty(request()->input('quick_add'))) {
            $quick_add = true;
        }

        return view('color.create')
            ->with(compact('quick_add'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('color.create')) {
            abort(403, 'Unauthorized action.');
        }
        try {
            $input = $request->only(['name', 'description', 'color_code']);
            $business_id = $request->session()->get('user.business_id');
            $input['business_id'] = $business_id;
            $input['created_by'] = $request->session()->get('user.id');

            $color = Color::create($input);
            $output = [
                'success' => true,
                'data' => $color,
                'msg' => __("color.added_success")
            ];
        } catch (\Exception $e) {
            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return redirect()->back()->with(['success' => $output]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
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
        if (!auth()->user()->can('color.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $color = Color::where('business_id', $business_id)->find($id);

            return view('color.edit')
                ->with(compact('color'));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('color.update')) {
            abort(403, 'Unauthorized action.');
        }

        // dd($request->input());
        if ($request->input('name')) {
            try {
                $input = $request->only(['name', 'description', 'color_code']);
                $business_id = $request->session()->get('user.business_id');

                $color = Color::where('business_id', $business_id)->findOrFail($id);
                $color->name = $input['name'];
                $color->description = $input['description'];
                $color->color_code = $input['color_code'];
                $color->save();

                $output = [
                    'success' => true,
                    'msg' => __("color.updated_success")
                ];
                return redirect()->back();
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
        if (!auth()->user()->can('color.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->user()->business_id;

                $color = Color::where('business_id', $business_id)->findOrFail($id);
                $color->delete();

                $output = [
                    'success' => true,
                    'msg' => __("color.deleted_success")
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
}
