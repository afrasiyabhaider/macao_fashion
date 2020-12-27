<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Sebdesign\VivaPayments\Client;

use GuzzleHttp\Exception\RequestException;
use Sebdesign\VivaPayments\Transaction;
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
     * 
     * @param  \Illuminate\Http\Request            $request
     * @param  \Sebdesign\VivaPayments\Transaction $transactions
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Transaction $transactions)
    {
        // dd($request);
        try {
            $transaction = $transactions->create([
                'PaymentToken' => $request->input('vivaWalletToken')
            ]);
            Cart::destroy();
            // dd($transaction);
        } catch (RequestException | VivaException $e) {
            report($e);
            // dd($e->getMessage());
            return back()->withErrors($e->getMessage());
        }

        // alert()->success('Paid','Payment paid successfully');
        return redirect(url('/'));
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
