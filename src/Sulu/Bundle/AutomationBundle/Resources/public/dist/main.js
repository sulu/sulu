require.config({paths:{suluautomation:"../../suluautomation/dist",suluautomationcss:"../../suluautomation/dist/css","services/suluautomation/task-manager":"../../suluautomation/dist/services/task-manager"}}),define(["css!suluautomationcss/main"],function(){return{name:"SuluAutomationBundle",initialize:function(a){"use strict";a.components.addSource("suluautomation","/bundles/suluautomation/dist/components")}}});