<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('APIToken')->group(function () {

    // enrouteAPI5.test/api/createNewDevice
    Route::get('/createNewDevice', 'apiController@newDevice')->name('api.newDevice');

    // enrouteAPI5.test/api/deviceExistAPI/1
    Route::get('/deviceExistAPI/{deviceID}', 'apiController@deviceExist')->name('api.checkDeviceExist');

    // enrouteAPI5.test/api/updateDeviceInfo
    Route::post('/updateDeviceInfo', 'apiController@updateDeviceInfo')->name('api.updateDeviceInfo');

    // enrouteAPI5.test/api/checkCompanyDisabled/115
    Route::get('/checkCompanyDisabled/{companyID}', 'apiController@checkCompanyDisabled')->name('api.checkCompanyDisabled');

    // enrouteAPI5.test/api/franchiseLink/115
    Route::get('/franchiseLink/{franchiseID}', 'apiController@franchiseLink')->name('api.franchiseLink');

    // enrouteAPI5.test/api/getSectorFile/595/53.525357436378,-113.02135620117
    // enrouteAPI5.test/api/getSectorFile/595/0.0000001,0.0000001
    Route::get('/getSectorFile/{deviceID}/{inputLocation}', 'apiController@getSectorFile')->name('api.getSectorFile');

});

// enrouteapi5.test/api/authenticate
Route::post('/authenticate', 'apiController@authenticate')->name('api.authenticate');
