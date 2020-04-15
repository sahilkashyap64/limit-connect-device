<?php
use Carbon\Carbon;
use Jenssegers\Agent\Agent;
/// check for 3 concurrent login
     function checkLoginLimit($user_id,$token){
        $login_limit = 3;
    //find all by user_id and and status=active
    $active_session = App\SessionTable::where([
    ['status', '=', '1'],
    ['users_id', '=', $user_id],
     ])->get()->toArray();
    // print_r ($active_session); 
    //echo count($active_session); exit;
        //check if there's no entry by this used id
        if ((count($active_session)==0)) {
         //new entry of sessionTable
         

        $date = date('Y-m-d H:i:s');
        
        //Store the login info in 
        $task = new  App\SessionTable();
        //$task->users_id = $request->users_id;
        $task->users_id = $user_id;
        $task->access_token = 'somrthing';//change this field to status
        $task->user_ip = getUserIpAddr();
        $task->mac_address = 'from login entry';
        $task->connected_devices = 1;
       $task->logintime = $date;;
        $task->status = 1;
        $task->created_on = $date;
        $task->modified_on = $date;
         
        if($task->save()){
        return response()->json([
      
          'success' => true,
          'token' => $token,
          'msg'=>'first logged in successfully'
          
          
      ]);}

        }
        else if (count($active_session) > 0 && count($active_session) < $login_limit) {

         //new entry of sessionTable
         //and get the number of last connected device
         $prevConnectDevicecount = App\SessionTable::select('connected_devices')
          ->where([
          ['status', '=', '1'],
          ['users_id', '=', $user_id],
           ])->orderBy('id', 'desc')
           ->first();
           
         //echo $prevConnectDevicecount['connected_devices']; 
        $device=number_format($prevConnectDevicecount['connected_devices']); 
        //the number of device previously connected + 1
        $newnumofDevice =$device+1; 
        $date = date('Y-m-d H:i:s');
        
        //Store the login info in 
        $task = new App\SessionTable();
        //$task->users_id = $request->users_id;
        $task->users_id = $user_id;
        $task->access_token = 'somrthing';//change this field to status
        $task->user_ip = getUserIpAddr();
        $task->mac_address = 'from login entry with +1 in connected device';
        $task->connected_devices= $newnumofDevice;
        $task->logintime = $date;;
        $task->status = 1;
        $task->created_on = $date;
        $task->modified_on = $date;
        $task->save();
        
       

    }else if (count(($active_session)) >= $login_limit) {
        
        // $prevConnectLoginIntime = App\SessionTable::select('logintime')->addSelect('id')
        //   ->where([
        //   ['status', '=', '1'],
        //   ['users_id', '=', $user_id],
        //    ])->orderBy('modified_on', 'desc')
        //    ->get()->toArray();
         //  print_r($prevConnectLoginIntime); 
          //get last login entry of the user
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
              echo "\r\n Login:";
              echo $logintime;
              echo "\r\n datetimeTwominutes ago:";
              echo $dateTimeMinutesAgo;
              if ($logintime <  $dateTimeMinutesAgo){
              //  echo "true";
              
               array_push($myarr,$fieldnam->id );
                
        // $fieldnam->logintime ;
          }
          
           }
           echo 'ids';
           print_r($myarr);
           //'connected_devices'=> DB::raw('connected_devices-1'),
           $decremen=count($myarr);// number of id we will use this number to decrement the last value
           $updatedExpireConcrenteUser=App\SessionTable::whereIn('id', $myarr)->update(['status' => 0,'modified_on' => Carbon::now()]);
         //  $updatedConnectedDeviceNum=App\SessionTable::whereIn('id', $myarr)->update(['connected_devices' => 0,'modified_on' => Carbon::now()]);
           
           if(!is_null($myarr)){

        //check max device connected with user_id
        
           $prevConnectDeviceMaxcount=App\SessionTable::where([
            ['status', '=', 1],
            ['users_id', '=',$user_id ]
        ])->orderBy('connected_devices','desc')->first(); echo 'Max count';
           $maxdevice=$prevConnectDeviceMaxcount['connected_devices']; 
          $lastid=$prevConnectDeviceMaxcount['id'];
           
           
           
           $newnumofDevice= $maxdevice-$decremen;  //newNumofDevice
           if(!$lastid== ''){
           
               
               App\SessionTable::where('id', $lastid)->update([
                'connected_devices' => $newnumofDevice
                ]);
            }
             }
           print_r($myarr); exit;
           
           if($updatedExpireConcrenteUser) {
            return response()->json([
                'success' => true,
                'msg'=>'hello',
                'myid'=>$myarr,
            ]);
        } 
         
           
         
    } //after iteration we check if the count of logins is still greater than the limit
    if (count($active_session) >= $login_limit) {
        //then return a login error that maximum logins reached
        //echo 'you are not allowed to login as you have breeched the maximum session limit.';
        //exit;
        
        

        echo 'you are not allowed to login as you have breeched the maximum session limit.';

    } else {
        
    return response()->json([
      
        'success' => true,
        'token' => $token,
        'msg'=>'logged in successfully'
        
        
    ]);
    }

    //update the logins column to equal to json_encode($logins);

    
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
