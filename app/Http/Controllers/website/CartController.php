<?php

namespace App\Http\Controllers\website;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Sebdesign\VivaPayments\Client;
use Sebdesign\VivaPayments\OAuth;

use App\Product;
use Cart;

class CartController extends Controller
{
    /**
     * Add to Cart 
     * 
     **/
    public function addToCart(Request $request)
    {
        $validate = Validator::make($request->input(),[
            'color' => 'required',
            'size' => ['required',Rule::notIn(0)]
        ]);
        if($validate->fails()){
            // alert()->error('Oops',$validate->errors()->first())->timerProgressBar();
            return redirect()->back();
        }
        $product = Product::find($request->product_id);
        Cart::add([
                'id' => $product->id, 
                'name' => $product->name, 
                'qty' => 1, 
                'price' => $product->variations()->first()['sell_price_inc_tax'],'weight' => 1,
                'options' => [
                    'size' => (int)$request->size,
                    'color'=> (int)$request->color,
                    'product_id'=> (int)$product->id,
                ]
            ]);
        // Cart::add(['id' => '2935555', 'name' => 'Product 1', 'qty' => 1, 'price' => 9.99, 'weight' => 550, 'options' => ['size' => 'large']]);
        
        // return Cart::content();
        // alert()->success('Yayy!','Product added into cart')->timerProgressBar();
        return redirect()->back();
    }
    /**
     * View Cart 
     * @param  \Sebdesign\VivaPayments\Client $client
     * @param  \Sebdesign\VivaPayments\OAuth  $oauth
     * @return \Illuminate\Http\Response
     **/
    public function viewCart(Client $client, OAuth $oauth)
    {
        try {
            $token = $oauth->requestToken();
        } catch (RequestException | VivaException $e) {
            report($e);

            return back()->withErrors($e->getMessage());
        }
        $cart = Cart::content()->toArray();
        
        return view('site.cart.cart', [
            'baseUrl' => $client->getApiUrl(),
            'accessToken' => $token->access_token,
            'cart' => $cart
        ]);
    }
    /**
     *  Remove Item from Cart
     *
     * */
    public function removeItem($id)
    {
        Cart::remove($id);
        // alert()->info('Ohh', 'Product removed from cart')->timerProgressBar();
        return redirect()->back();
    }
    /**
     *  Empty Cart
     *
     * */
    public function emptyCart()
    {
        Cart::destroy();
        // alert()->info('Ohh', 'Cart empty')->timerProgressBar();
        return redirect()->back();
    }
    /**
     *  Update Cart
     *
     * */
    public function updateCartItem($id,$qty)
    {
        Cart::update($id, ['qty' => $qty]);
        // alert()->success('Yayy', 'Cart updated')->timerProgressBar();
        return redirect()->back();
    }
}
