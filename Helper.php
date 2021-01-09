<?php
use Carbon\Carbon;
use App\SessionTable;
use Jenssegers\Agent\Agent;
     function checkLoginLimit($user_id,$token,$deviceDetails){
        $login_limit = 5;
    //find all by user_id and and status=active
    $active_session = App\SessionTable::where([
    ['status', '=', '1'],
    ['users_id', '=', $user_id],
     ])->get()->toArray();
        //check if there's no entry by this used id
        if ((count($active_session)==0)) {
         //new entry of sessionTable
         sessionTable($user_id,$token,$deviceDetails,$connected_devices=1);
     } else if (count($active_session) > 0 && count($active_session) < $login_limit) {
          //check if device id is same
          if(duplicateDeviceId($user_id,$deviceDetails)==false){
         //new entry of sessionTable
         //and get the number of last connected device
         $prevConnectDevicecount = App\SessionTable::select('connected_devices')
          ->where([
          ['status', '=', '1'],
          ['users_id', '=', $user_id],
           ])->orderBy('connected_devices', 'desc')
           ->first();
           
        $device=number_format($prevConnectDevicecount['connected_devices']); 
        //the number of device previously connected + 1
        sessionTable($user_id,$token,$deviceDetails,$connected_devices=$device+1);
        }

    } //after iteration we check if the count of logins is still greater than the limit
    if (count($active_session) >= $login_limit) {
        //then return a login error that maximum logins reached
        //echo 'you are not allowed to login as you have breeched the maximum session limit.';
        //exit;
        return response()->json([
          'success' => false,
          'response_code'=>429,
          'message'=>'Your account is in use on 5 devices. Please stop playing on other devices to continue',
          //'loggedindeviceinfointheseId'=>$myarr,
        ],429)->send();
        


    }


    
}
function automaticLogoutWithInterval($user_id){
  $login_limit = 3;
  //find all by user_id and and status=active
  $active_session = App\SessionTable::where([
  ['status', '=', '1'],
  ['users_id', '=', $user_id],
   ])->get()->toArray();
if (count(($active_session)) >= $login_limit) {
  //automatic logout
  // $prevConnectLoginIntime = App\SessionTable::select('logintime')->addSelect('id')
  //   ->where([
  //   ['status', '=', '1'],
  //   ['users_id', '=', $user_id],
  //    ])->orderBy('modified_on', 'desc')
  //    ->get()->toArray();
   //  print_r($prevConnectLoginIntime); 
    //get last login entry of the 
    
     $data = App\SessionTable::select('logintime')->addSelect('id')
     ->where([
     ['status', '=', '1'],
     ['users_id', '=', $user_id],
      ])->orderBy('modified_on', 'desc')
      ->get();
      $myarr=[];
      foreach($data as $fieldnam) {
        //increase this time when creating cms for now time is 2 minutes
        $logintime=$fieldnam->logintime;
        $dateTimeMinutesAgo = new DateTime("2 minutes ago");
        $dateTimeMinutesAgo = $dateTimeMinutesAgo->format("Y-m-d H:i:s");
      //   echo "\r\n Login:";
      //   echo $logintime;
      //   echo "\r\n datetimeTwominutes ago:";
      //   echo $dateTimeMinutesAgo;
        if ($logintime <  $dateTimeMinutesAgo){
        //  echo "true";
        
         array_push($myarr,$fieldnam->id );                
  
    }
    
     }
     //echo 'ids';
    // print_r($myarr);
     //'connected_devices'=> DB::raw('connected_devices-1'),
     $decremen=count($myarr);// number of id we will use this number to decrement the last value
     $updatedExpireConcrenteUser=App\SessionTable::whereIn('id', $myarr)->update(['status' => 0,'modified_on' => Carbon::now()]);
   //  $updatedConnectedDeviceNum=App\SessionTable::whereIn('id', $myarr)->update(['connected_devices' => 0,'modified_on' => Carbon::now()]);
     
     if(!is_null($myarr)){

  //check max device connected with user_id
  
     $prevConnectDeviceMaxcount=App\SessionTable::where([
      ['status', '=', 1],
      ['users_id', '=',$user_id ]
  ])->orderBy('connected_devices','desc')->first(); //echo 'Max count';
     $maxdevice=$prevConnectDeviceMaxcount['connected_devices']; 
    $lastid=$prevConnectDeviceMaxcount['id'];
     
     
     
     $newnumofDevice= $maxdevice-$decremen;  //newNumofDevice
     if(!$lastid== ''){
     
         //now update the new number of device in last active id
         App\SessionTable::where('id', $lastid)->update([
          'connected_devices' => $newnumofDevice
          ]);
      }
       }
     
     
     if($updatedExpireConcrenteUser) {
       
      return response()->json([
          'success' => true,
          'msg'=>'logged out automatically successful',
          'myid'=>$myarr,
      ])->send();
  } 
   
     
 //automatic logout finish  
}}

function LogoutandDecrementDevice($user_id){
  

  //logout
  $myarr=[];
  array_push($myarr,$user_id ); 
     
     $decremen=count($myarr);// number of id we will use this number to decrement the last value
     $updatedExpireConcrenteUser=App\SessionTable::whereIn('id', $myarr)->update(['status' => 0,'modified_on' => Carbon::now()]);
     
     
 //if logout happens minus one from all the active device of user
 //UPDATE `session_table` SET `connected_devices` = `connected_devices` - 1 WHERE `status` = 1 AND `connected_devices`<> 1
    App\SessionTable::where([
            ['status', '=', '1'],
            ['connected_devices', '!=', '1'],
             ])
    ->update([
        'connected_devices' => DB::raw('`connected_devices` - 1 ')
    ]);
    config([
      'jwt.blacklist_enabled' => true
  ]);
    JWTAuth::parseToken()->invalidate();
      return response()->json([
        'response_code'=>200,
        'success' => true,
        'message'=>'logged out successful',
        
      ],200)->send();
    
     
}

function sessionTable($user_id,$token,$deviceDetails,$connected_devices){
  $userInfo = JWTAuth::user();
  if (App\SessionTable::where('users_id', '=',$user_id)->count()== 0) {
    $termAndCondition=true;
 } else {$termAndCondition=false;}
  

  if(sessionTableStore($user_id,$token,$deviceDetails,$connected_devices)){
    $active_sessioncount = App\SessionTable::where([
      ['status', '=', '1'],
      ['users_id', '=', $user_id],
       ])->get()->count();
    return response()->json([
  
      'success' => true,
      'message'=>'logged in successfully ',
      'response_code'=>200,
      'connected_device'=> $active_sessioncount.' device connected  ',
      'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => JWTAuth::factory()->getTTL() * 60,
        'device_info'=>$deviceDetails,
        'user'=>$userInfo,
        'termandcondition'=>$termAndCondition
        
      
      
    ],200)->send();}

}

function duplicateDeviceId($user_id,$deviceDetails){
  if (App\SessionTable::where([
    ['status', '=', '1'],
    ['users_id', '=', $user_id],
    ['mac_address', '=', $deviceDetails],
     ])->exists()) {
      $explodedstring= explode("#~",$deviceDetails);
       return response()->json([
        'success' => false,
        'response_code'=>429,
        'message'=>'Same device('.$explodedstring['0'].') ID = '.$explodedstring['1'].' detected,Kindly remove your session first or remove the device from web!',
        //'loggedindeviceinfointheseId'=>$myarr,
      ],429)->send();
    // exists
} else{
  return false;
}
}

function sessionTableStore($user_id,$token,$deviceDetails,$connected_devices){
  $date = date('Y-m-d H:i:s');
$session_tab = new SessionTable();
$session_tab->users_id = $user_id;
$session_tab->access_token = $token;
$session_tab->user_ip = getUserIpAddr();
$session_tab->mac_address = $deviceDetails;
$session_tab->connected_devices = $connected_devices;
$session_tab->logintime = Carbon::now();
$session_tab->status = 1;
$session_tab->created_on = $date;
$session_tab->modified_on = Carbon::now();  
return $session_tab->save();
}

function getUserIpAddr(){
  if(!empty($_SERVER['HTTP_CLIENT_IP'])){
      //ip from share internet
      $ip = $_SERVER['HTTP_CLIENT_IP'];
  }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
      //ip pass from proxy
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
  }else{
      $ip = $_SERVER['REMOTE_ADDR'];
  }
  return $ip;
}


?>
