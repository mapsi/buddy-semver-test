<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Http\Controllers\AppBaseController;
use Flash;
use Illuminate\Http\Request;
use Prettus\Repository\Criteria\RequestCriteria;
use Response;

class ProductController extends AppBaseController
{
    /** @var  ProductsRepository */
    private $productRepository;

    public function __construct(ProductRepository $productsRepo)
    {
        $this->productRepository = $productsRepo;
    }

    public function lookup(Request $request)
    {
        $products = Product::orWhere('name', 'like', '%' . $request->input('q') . '%')->limit(10)->get();

        return $products->transform(function ($item) {
            return [
              'id' => $item->id,
              'text' => $item->name
            ];
        });
    }

    /**
     * Display a listing of the Products.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $this->productRepository->pushCriteria(new RequestCriteria($request));

        $products = $this->productRepository->paginate();

        return view('products.index')->with('products', $products);
    }

    /**
     * Show the form for creating a new Products.
     *
     * @return Response
     */
    public function create()
    {
        return view('products.create')->with('brands', Brand::all());
    }

    /**
     * Store a newly created Products in storage.
     *
     * @param CreateProductRequest $request
     *
     * @return Response
     */
    public function store(CreateProductRequest $request)
    {
        $input = $request->all();

        $products = $this->productRepository->create($input);

        Flash::success('Products saved successfully.');

        return redirect(route('products.index'));
    }

    /**
     * Display the specified Products.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $products = $this->productRepository->findWithoutFail($id);

        if (empty($products)) {
            Flash::error('Products not found');

            return redirect(route('products.index'));
        }

        return view('products.show')->with('products', $products);
    }

    /**
     * Show the form for editing the specified Products.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $products = $this->productRepository->findWithoutFail($id);

        if (empty($products)) {
            Flash::error('Products not found');

            return redirect(route('products.index'));
        }

        return view('products.edit')->with('products', $products)->with('brands', Brand::all());
    }

    /**
     * Update the specified Products in storage.
     *
     * @param  int              $id
     * @param UpdateProductsRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateProductRequest $request)
    {
        $products = $this->productRepository->findWithoutFail($id);

        if (empty($products)) {
            Flash::error('Products not found');

            return redirect(route('products.index'));
        }

        $products = $this->productRepository->update($request->all(), $id);

        Flash::success('Products updated successfully.');

        return redirect(route('products.index'));
    }

    /**
     * Remove the specified Products from storage.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $products = $this->productRepository->findWithoutFail($id);

        if (empty($products)) {
            Flash::error('Product not found');

            return redirect(route('products.index'));
        }
        if ($products->subscriptions->count()) {
            Flash::error('There are subscriptons for this products please delete them first');

            return redirect(route('products.index'));
        }
        $this->productRepository->delete($id);

        Flash::success('Product deleted successfully.');

        return redirect(route('products.index'));
    }
}
