/*browser:true*/
/*global define*/

define([
  "uiComponent",
  "Magento_Checkout/js/model/payment/renderer-list",
], function (Component, rendererList) {
  "use strict";

  rendererList.push({
    type: "pago46method",
    component:
      "Pago46_Cashin/js/view/payment/method-renderer/pago46method-method",
  });

  return Component.extend({});
});
