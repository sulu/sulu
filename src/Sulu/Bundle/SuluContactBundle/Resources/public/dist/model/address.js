define(["mvc/relationalmodel","mvc/hasone","sulucontact/model/country","sulucontact/model/addressType"],function(a,b,c,d){return a({urlRoot:"",defaults:{id:null,street:"",number:"",addition:"",zip:"",city:"",state:"",country:null,addressType:null},relations:[{type:b,key:"country",relatedModel:c},{type:b,key:"addressType",relatedModel:d}]})});