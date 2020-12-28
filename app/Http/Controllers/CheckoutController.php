<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Sebdesign\VivaPayments\Client;

use GuzzleHttp\Exception\RequestException;
use Sebdesign\VivaPayments\NativeCheckout;
use Sebdesign\VivaPayments\OAuth;
use Sebdesign\VivaPayments\VivaException;

use Cart;

class CheckoutController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Display the payment button.
     * 
     * @param  \Sebdesign\VivaPayments\Client $client
     * @return \Illuminate\Http\Response
     */
    public function create(Client $client)
    {
        return view('site.checkout', [
            'publicKey' => config('services.viva.public_key'),
            'baseUrl' => $client->getUrl(),
        ]);
    }

    /**
     * Create a new transaction with the charge token from the form.
     *
     * @param  \Illuminate\Http\Request               $request
     * @param  \Sebdesign\VivaPayments\OAuth          $oauth
     * @param  \Sebdesign\VivaPayments\NativeCheckout $nativeCheckout
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, OAuth $oauth, NativeCheckout $nativeCheckout)
    {
        // dd($request);
        try {
            $oauth->requestToken();

            $transactionId = $nativeCheckout->createTransaction([
                'amount' => 1000,
                'tipAmount' => 0,
                'preauth' => false,
                'chargeToken' => $request->input('chargeToken'),
                'installments' => $request->input('installments'),
                'merchantTrns' => 'Merchant transaction reference',
                'customerTrns' => 'Description that the customer sees',
                'currencyCode' => 826,
                'customer' => [
                    'email' => 'native@vivawallet.com',
                    'phone' => '442037347770',
                    'fullname' => 'John Smith',
                    'requestLang' => 'en',
                    'countryCode' => 'GB',
            ]);
        } catch (RequestException | VivaException $e) {
            report($e);
            return redirect()->back()->withErrors($e->getMessage());
        }
        // alert()->success('Paid','Payment paid successfully');
        return redirect()->back();
        // return redirect('order/success');
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
