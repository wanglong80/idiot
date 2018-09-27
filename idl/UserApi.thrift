namespace java com.xintiaotime.thrift.demo

service UserApi {
    string getUserName(1:string userId);
}