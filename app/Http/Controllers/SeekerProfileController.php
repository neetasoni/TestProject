<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use  App\SeekerProfile;
use Validator;
use Illuminate\Http\Request;
use \Illuminate\Http\Response as Res;
use DB;
class SeekerProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function viewAllSekers(Request $request)
    {
        $postData = $request->all();
        $data= array();
        
        $validator = Validator::make($request->all(), [              
              
              'pagesize'=>'present|integer',
              'sortby'=>'sometimes',
              'search'=>'sometimes',
              'pageno'=>'present|integer',
        ]);
        
        if ($validator->fails()) 
        {
          $missingData = $validator->errors();
          $responseUserMsg = trans('apiMessages.mobileApp.request_validation_fail');
          $responseDevMsg = $missingData;
          $responseStatusCode = RES::HTTP_BAD_REQUEST;
          return $this->respondError('HTTP_INTERNAL_SERVER_ERROR',$responseStatusCode,$responseUserMsg,$responseDevMsg);
        }
        $perPage = $postData['pagesize'];
        $pageNo = $postData['pageno'];
        //Get  the offset 
        $offset = ($pageNo -1)*$perPage;
        $query = DB::table('seekerProfile')
                ->offset($offset)
                ->limit($perPage);  
        if(!empty($postData['sortby']))
            $query->orderby($postData['sortby']);   
        if(!empty($postData['search']))
        {
            $query->where('name',$postData['search']);            
            $query->orwhere('location',$postData['search']);            
        }
        $SeekerResult = $query->get();
        $returnResult = array();
        foreach ($SeekerResult as $key => $value) 
        {
            array_push($returnResult, 
                array(  'id'=>$value->id,'name'=>$value->name,
                        'email'=>$value->email,'number'=>$value->number,
                        'location'=>$value->location,'description'=>$value->description,
                        'photo'=>$value->photo,'gallery'=>json_decode($value->gallery),
                        'created_at'=>$value->created_at,'updated_at'=>$value->updated_at
                ));
        }      

        return response()->json(['seekerProfile' =>  $returnResult], 200);
    }
    public function createSeeker(Request $request)
    {
     //This api is not used anywhere.This created to create Guest customer for merchant.
     
        $postData = $request->all();

        
        $validator = Validator::make($postData, [
            'name'=>'required',
            'email'=>'required|email',     
            "location"=>'required',
            "description"=>'required', 
            "photo"=>'required',
            "gallery"=>'required|array',
            "number"=>'required',
        ]);

        //first vaildate the request.If fails return
        if ($validator->fails()) {
            $missingData = $validator->errors();

            $responseUserMsg = trans('apiMessages.mobileApp.request_validation_fail');
            $responseDevMsg = $missingData;
            $responseStatusCode = RES::HTTP_BAD_REQUEST;
            return $this->respondError('HTTP_INTERNAL_SERVER_ERROR',$responseStatusCode,$responseUserMsg,$responseDevMsg);
           
        }  
    
        try
        {
            $count = $this->checkUniqueSeeker($postData['email'],$postData['number'] );
            if($count>0)
            {
                $responseUserMsg ='Duplicate Record';
                $responseDevMsg ='Duplicate Record';
                $responseStatusCode = RES::HTTP_INTERNAL_SERVER_ERROR;
                return $this->respondError('HTTP_INTERNAL_SERVER_ERROR',$responseStatusCode,$responseUserMsg,$responseDevMsg);
            }

            $photoPath = $this->uploadImage($postData['photo']);
            $galleryImgPath=array();
            foreach($postData['gallery'] as $gImg)
            {
                array_push($galleryImgPath,$this->uploadGalleryImage($gImg));
            }
            
            DB::table('seekerProfile')->insert([
                'name' => $postData['name'] ,
                'location' => $postData['location'] ,
                'description' => $postData['description'] ,
                'email' => $postData['email'] ,
                'photo' => $photoPath ,
                'gallery' => json_encode($galleryImgPath),
                'number'=>$postData['number'] ,
                'created_at'=>date('Y-m-d')
            ]);
            $rslReturn=array("msg"=>'Data Inserted Successfully');
            return $this->respondSuccess($rslReturn);

        }
        catch(\exception $e)
        {   
               
            $responseUserMsg =$e->getMessage();
            $responseDevMsg = $e->getMessage();
            $responseStatusCode = RES::HTTP_INTERNAL_SERVER_ERROR;
            return $this->respondError('HTTP_INTERNAL_SERVER_ERROR',$responseStatusCode,$responseUserMsg,$responseDevMsg);
        }
     
    }
    private function checkUniqueSeeker($email,$number,$id=0)
    {
        
        $query = DB::table('seekerProfile')
        ->where('number',$number)
        ->where('email',$email);
        if($id>0)
            $query->where('id','!=',$id);
        $count=$query->count();
        return $count;
    }
    public function respondError($id=null,$responseStatusCode,$responseUserMsg,$responseDevMsg)
    {
        $arrError=array();
        
        array_push($arrError,array(
            'id'=>$id,
            'status_code'=>$responseStatusCode,
            'userMessage'=>$responseUserMsg,
            'developerMessage'=>$responseDevMsg)                
            );
        return response()->json([               
                'data' => array(),
                'error'=> $arrError
                ],$responseStatusCode);

    }
    public function respondSuccess($data)
     { 
                
        $response = response()->json([          
            'data' =>$data,
            'error'=>array()
            ],RES::HTTP_OK);
        
        return $response;
                                    
    }
    public function uploadImage($fileName)
    {
       
        $imgUrl=base_path() . '/public/'.$fileName;        
        $image = file_get_contents($imgUrl);
        $destinationPath = base_path() . '/public/images/upload/' . $fileName;

        file_put_contents($destinationPath, $image);
       return $destinationPath;
        
    }
    public function uploadGalleryImage($fileName)
    {
       
        $imgUrl=base_path() . '/public/GalleryImg/'.$fileName;        
        $image = file_get_contents($imgUrl);
        $destinationPath = base_path() . '/public/images/upload/' . $fileName;

        file_put_contents($destinationPath, $image);
       return $destinationPath;
        
    }
    public function updateSeeker(Request $request)
    {
     //This api is not used anywhere.This created to create Guest customer for merchant.
     
        $postData = $request->all();        
        $validator = Validator::make($postData, [
            'id'=>'required|integer',
            'name'=>'required',
            'email'=>'required|email',     
            "location"=>'required',
            "description"=>'required', 
            "photo"=>'required',
            "gallery"=>'required|array',
            "number"=>'required',
        ]);

        //first vaildate the request.If fails return
        if ($validator->fails()) {
            $missingData = $validator->errors();

            $responseUserMsg = trans('apiMessages.mobileApp.request_validation_fail');
            $responseDevMsg = $missingData;
            $responseStatusCode = RES::HTTP_BAD_REQUEST;
            return $this->respondError('HTTP_INTERNAL_SERVER_ERROR',$responseStatusCode,$responseUserMsg,$responseDevMsg);
           
        }  
    
        try
        {
            $count = $this->checkUniqueSeeker($postData['email'],$postData['number'],$postData['id'] );
            if($count>0)
            {
                $responseUserMsg ='Duplicate Record';
                $responseDevMsg ='Duplicate Record';
                $responseStatusCode = RES::HTTP_INTERNAL_SERVER_ERROR;
                return $this->respondError('HTTP_INTERNAL_SERVER_ERROR',$responseStatusCode,$responseUserMsg,$responseDevMsg);
            }

            $photoPath = $this->uploadImage($postData['photo']);
            $galleryImgPath=array();
            foreach($postData['gallery'] as $gImg)
            {
                array_push($galleryImgPath,$this->uploadGalleryImage($gImg));
            }
            
            DB::table('seekerProfile')->where('id',$postData['id'])
            ->update([
                'name' => $postData['name'] ,
                'location' => $postData['location'] ,
                'description' => $postData['description'] ,
                'email' => $postData['email'] ,
                'photo' => $photoPath ,
                'gallery' => json_encode($galleryImgPath),
                'number'=>$postData['number'] ,
                'updated_at'=>date('Y-m-d')
            ]);
            $rslReturn=array("msg"=>'Data Updated Successfully');
            return $this->respondSuccess($rslReturn);

        }
        catch(\exception $e)
        {   
               
            $responseUserMsg =$e->getMessage();
            $responseDevMsg = $e->getMessage();
            $responseStatusCode = RES::HTTP_INTERNAL_SERVER_ERROR;
            return $this->respondError('HTTP_INTERNAL_SERVER_ERROR',$responseStatusCode,$responseUserMsg,$responseDevMsg);
        }
     
    }

    public function deleteSeeker(Request $request)
    {
     //This api is not used anywhere.This created to create Guest customer for merchant.
     
        $postData = $request->all();        
        $validator = Validator::make($postData, [
            'id'=>'required|integer'            
        ]);

        //first vaildate the request.If fails return
        if ($validator->fails()) {
            $missingData = $validator->errors();
            $responseUserMsg = trans('apiMessages.mobileApp.request_validation_fail');
            $responseDevMsg = $missingData;
            $responseStatusCode = RES::HTTP_BAD_REQUEST;
            return $this->respondError('HTTP_INTERNAL_SERVER_ERROR',$responseStatusCode,$responseUserMsg,$responseDevMsg);
           
        }  
    
        try
        {            
            DB::table('seekerProfile')->where('id',$postData['id'])
            ->delete();
            $rslReturn=array("msg"=>'Data Deleted Successfully');
            return $this->respondSuccess($rslReturn);
        }
        catch(\exception $e)
        {   
               
            $responseUserMsg =$e->getMessage();
            $responseDevMsg = $e->getMessage();
            $responseStatusCode = RES::HTTP_INTERNAL_SERVER_ERROR;
            return $this->respondError('HTTP_INTERNAL_SERVER_ERROR',$responseStatusCode,$responseUserMsg,$responseDevMsg);
        }
     
    }
}
