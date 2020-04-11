<?php

namespace App\Http\Controllers\Admin;

use App\Helper\Reply;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Product;
use App\Tax;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class AdminProductController extends AdminBaseController
{
    /**
     * AdminProductController constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->pageTitle = 'app.menu.products';
        $this->pageIcon = 'icon-basket';
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->totalProducts = Product::count();
        return view('admin.products.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->taxes = Tax::all();
        return view('admin.products.create', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreProductRequest $request)
    {
        $products = new Product();
        $products->name = $request->name;
        $products->price = $request->price;
        $products->taxes = $request->tax ? json_encode($request->tax) : null;
        $products->description = $request->description;
        $products->allow_purchase = ($request->purchase_allow == 'no')? true : false ;
        $products->save();

        return Reply::redirect(route('admin.products.index'), __('messages.productAdded'));
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
        $this->product = Product::find($id);
        $this->taxes = Tax::all();

        return view('admin.products.edit', $this->data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateProductRequest $request, $id)
    {
        $products = Product::find($id);
        $products->name = $request->name;
        $products->price = $request->price;
        $products->taxes = $request->tax ? json_encode($request->tax) : null;
        $products->description = $request->description;
        $products->allow_purchase = ($request->purchase_allow == 'no')? true : false ;
        $products->save();

        return Reply::redirect(route('admin.products.index'), __('messages.productUpdated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Product::destroy($id);
        return Reply::success(__('messages.productDeleted'));
    }

    /**
     * @return mixed
     */
    public function data()
    {
        $products = Product::select('id', 'name', 'price', 'taxes', 'allow_purchase')
            ->get();

        return DataTables::of($products)
            ->addColumn('action', function($row){
                return '<a href="'.route('admin.products.edit', [$row->id]).'" class="btn btn-info btn-circle"
                      data-toggle="tooltip" data-original-title="Edit"><i class="fa fa-pencil" aria-hidden="true"></i></a>

                      <a href="javascript:;" class="btn btn-danger btn-circle sa-params"
                      data-toggle="tooltip" data-user-id="'.$row->id.'" data-original-title="Delete"><i class="fa fa-times" aria-hidden="true"></i></a>';
            })
            ->editColumn('name', function ($row) {
                    return ucfirst($row->name);
            })
            ->editColumn('price', function ($row) {
                if (!is_null($row->taxes)) {
                    $totalTax = 0;
                    foreach (json_decode($row->taxes) as $tax) {
                        $this->tax = Product::taxbyid($tax)->first();
                        $totalTax = $totalTax + ($row->price * ($this->tax->rate_percent / 100));
                    }
                    return $this->global->currency->currency_symbol . ($row->price + $totalTax);
                } else {
                    return $this->global->currency->currency_symbol . $row->price;
                }
            })
            ->editColumn('allow_purchase', function ($row) {
                if ($row->allow_purchase == 1) {
                    return '<label class="label label-success">' . __('app.allowed') . '</label>';
                }else {
                    return '<label class="label label-danger">' . __('app.allowed') . '</label>';
                }
            })
            ->rawColumns(['action', 'allow_purchase'])
            ->make(true);
    }

    public function export() {
        $attributes =  ['tax', 'taxes', 'price'];
        $products = Product::select('id', 'name', 'price')
            ->get()->makeHidden($attributes);

            // Initialize the array which will be passed into the Excel
        // generator.
        $exportArray = [];

        // Define the Excel spreadsheet headers
        $exportArray[] = ['ID', 'Name', 'Price'];

        // Convert each member of the returned collection into an array,
        // and append it to the payments array.
        foreach ($products as $row) {
            $rowArrayData = $row->toArray();
            $rowArrayData['total_amount'] = $this->global->currency->currency_symbol.$rowArrayData['total_amount'];
            $exportArray[] = $rowArrayData;
        }

        // Generate and return the spreadsheet
        Excel::create('Product', function($excel) use ($exportArray) {

            // Set the spreadsheet title, creator, and description
            $excel->setTitle('Product');
            $excel->setCreator('Worksuite')->setCompany($this->companyName);
            $excel->setDescription('Product file');

            // Build the spreadsheet, passing in the payments array
            $excel->sheet('sheet1', function($sheet) use ($exportArray) {
                $sheet->fromArray($exportArray, null, 'A1', false, false);

                $sheet->row(1, function($row) {

                    // call row manipulation methods
                    $row->setFont(array(
                        'bold'       =>  true
                    ));

                });

            });



        })->download('xlsx');
    }
}
