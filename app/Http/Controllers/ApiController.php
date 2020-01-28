<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Dirape\Token\Token;

class ApiController extends Controller
{
    public function newDevice(){

        $id = DB::table('devices')->insertGetId([
            'ip_addr' => '0.0.0.0', 
            ]
        );

        $registerCode = $this->updateRegisterCode($id);

        return response()->json([
            'id' => $id,
            'code' => $registerCode
        ]);
    }

    public function deviceExist($deviceID){
        $device = DB::table('devices')->find($deviceID);

        if($device === null)
            return response()->json([
                'message' => 'ID not exist',
            ]);

        return response()->json([
            'id' => $device->id,
            'mac_addr' => $device->mac_addr,
            'pass' => $device->pass,
        ]);
    }

    public function updateRegisterCode($deviceID){
        if( DB::table('devices')->find($deviceID) === null)
            return response()->json([
                'message' => 'ID not exist',
            ]);

        $registerCode = strtoupper((new Token())->Unique('devices', 'code', 6));

        DB::table('devices')
            ->where('id', $deviceID)
            ->update(['code' => $registerCode ]);

        return $registerCode;
    }

    public function updateDeviceInfo(Request $request){

        if( DB::table('devices')->find($request->id) === null)
            return response()->json([
                'message' => 'ID not exist',
            ]);

        DB::table('devices')
            ->where('id', $request->id)
            ->update([
                'version' => $request->app_version,
                'mac_addr' => $request->mac_address,
                'ip_addr' => $request->ip_address,
                'disk_size' => $request->total_device_space,
                'disk_used' => $request->total_device_space - $request->space_left,
                ]);

        return response()->json([
            'message' => 'Update Success!',
        ]);
    }

    public function checkCompanyDisabled($companyID){
        $disabled = DB::table('companies')
            ->select('disabled')
            ->where('id', $companyID);
        
        return response()->json([
            'disabled' => $disabled === '1' ? 'true' : 'false' ,
        ]);
    }

    public function franchiseLink($franchiseID){
        $linkCollections = DB::table('franchise_sectors')
            ->select('tablet_links')
            ->where('franchise', $franchiseID)
            ->get();

        if( sizeof($linkCollections) == 0){
            return response()->json([
                'message' => 'Franchise ID not found'
            ]);
        }

        $objectArray = array();
        foreach($linkCollections as $collection){
            
            if($collection->tablet_links != null){
                
                //remove several specific character
                $replacedString = str_replace( array('{', '}', '\\'), '', $collection->tablet_links );
                //seperate whole string into array
                $stringToArrays = explode(',', $replacedString);

                foreach($stringToArrays as $array){
                    $obj = $this->convert_string_to_object($array);
                    array_push($objectArray, $obj);
                }
            }
        }

        return response()->json([
            'FranchiseLink' => $objectArray
        ]);
    }

    // Convert a STRING contain title and links into an OBJECT with title and links
    function convert_string_to_object($arr){
        $arrayToObject = array();

        $str = str_replace( '":"', '" "', $arr ); 
        $arr = explode(' ', $str); 

        $title = $this->get_string_between($arr[0], '"', '"');

        //checking if the string contains more than 1 link
        if(substr_count($arr[1], '"') > 3){
            $arr[1] = str_replace( array('""', '"'), array(' ', ''), $arr[1] );
            $linkArray = explode(' ', $arr[1]);

            foreach($linkArray as $link )
                array_push($arrayToObject, $link);                         
        }else{
            $link = $this->get_string_between($arr[1], '"', '"');
            array_push($arrayToObject, $link);
        }        
    
        $obj = app()->make('stdClass');
        $obj->title = $title;
        $obj->links = $arrayToObject;

        return $obj;
    }

    // Get the string between 2 characters
    function get_string_between($string, $start, $end){
        $string = " ".$string;
        $ini = strpos($string,$start);
        if ($ini == 0) return "";
        $ini += strlen($start);   
        $len = strpos($string,$end,$ini) - $ini;
        return substr($string,$ini,$len);
    }

    public function getSectorFile($deviceID, $inputLocation){
        $location = explode(',', $inputLocation);

        $sector = DB::table('sectors')
            ->where('lat_NW', '>', $location[0])
            ->where('lat_SE', '<', $location[0])
            ->where('lon_NW', '<', $location[1])
            ->where('lon_SE', '>', $location[1])
            ->get()->first();
            
        if($sector === null){
            $companyID = DB::table('devices')
                            ->select('franchise')
                            ->where('id', $deviceID)
                            ->get()->first()->franchise;

            $firstSector = DB::table('sectors')
                            ->where('id', 
                                    DB::table('franchise_sectors')
                                    ->where('franchise', $companyID)
                                    ->get()->first()->sector)
                            ->get()->first();

            $path = $this->create_sector_file($firstSector, $location, $companyID);
            
        }else{
            $path = $this->create_sector_file($sector, $location);
        }

        return response()->download($path)->deleteFileAfterSend(true);
    }

    function create_sector_file($sector, $location, $companyID = null){
        $sectorLat = array($sector->lat_NW, $sector->lat_SE);
        $sectorLon = array($sector->lon_NW, $sector->lon_SE);

        if($companyID !== null){
            $regionsTable = DB::table('regions')
                ->where('sector', $sector->id)
                ->where('disabled', 0)
                ->where('inactive', 0)
                ->where('owner', $companyID) 
                ->get();
        }else{
            $regionsTable = DB::table('regions')
                ->where('sector', $sector->id)
                ->where('disabled', 0)
                ->where('inactive', 0)
                ->get();
        }

        ////////Begin make the file
        $xml = new \SimpleXMLElement('<regionMap />');

        $xml->addChild('offset', $location[0].', '.$location[1]);
        $xml->addChild('precision', $sector->precision);
        $xml->addChild('timezone', $sector->timezone);
        $xml->addChild('height', $sector->height);
        $xml->addChild('width', $sector->width);

        $regions = $xml->addChild('regions');

        foreach($regionsTable as $reg){
            $medias = DB::table('region_media_junctions')
                ->where('region', $reg->id)->get();
            $reg->media = $medias;

            foreach($reg->media as $mID){
                $media = DB::table('media')->find($mID->media);

                $region = $regions->addChild('region');
                $region->addChild('name', 'Ad_'.$media->id.'.'.$media->type.' ('.$media->position.')');
                $region->addChild('owner', $media->owner);
                $region->addChild('type', $media->type);
                $region->addChild('expanded', $reg->expanded === 1 ? 'true' : 'false');
                $region->addChild('ad_url', $media->url);
                $region->addChild('reg_id', $reg->id);
                $region->addChild('timeslot', $reg->start_time.', '.$reg->end_time);
                $region->addChild('seconds', $media->seconds);
                $area_points = $region->addChild('area_points');
                foreach($sectorLat as $lat)
                    foreach($sectorLon as $lon)
                        $area_points->addChild('coord', $lat.', '.$lon);
            }
        }
        
        //delete the file if exist ( just for testing )
        if(Storage::disk('local')->exists($sector->name.'.xml'))
            Storage::delete($sector->name.'.xml');

        //DOMDocument will get the data from $xml
        //and return the xml file with nice format
        $dom = new \DOMDocument("1.0");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        Storage::disk('local')->put($sector->name.'.xml', $dom->saveXML() );
        
        return storage_path('app/'.$sector->name.'.xml');
    }

    public function authenticate(Request $request){
        $device = DB::table('devices')->find($request->device_id);

        if($device === null)
            return response()->json([
                'message' => 'The device ID not found!',
                'status' => 'Unsuccess',
            ]);
        
        if($device->pass == "" && $device->code != "")    
            return response()->json([
                'message' => 'The device need to register',
                'status' => 'Unsuccess',
            ]);

        if( $request->password === $device->pass ){

            $access_token = (new Token())->Unique('devices', 'code', 60);

            DB::table('devices')
                ->where('id', $device->id)
                ->update(['access_token' => $access_token ]);

            return response()->json([
                'status' => 'Success',
                'access_token' => $access_token,
            ]);
        }      
        else
            return response()->json([
                'message' => 'The Password is incorrect',
                'status' => 'Unsuccess',
            ]);     
    }

}
