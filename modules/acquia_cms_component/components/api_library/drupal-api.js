'use strict';

window.DrupalApi = function() {};

DrupalApi.prototype = {
  baseUrl: "/jsonapi/",
  setBaseUrl: function(baseUrl) {
    this.baseUrl = baseUrl;
  },
  setEndpoint: function (endpoint) {
    if (typeof(endpoint) == "string") {
      this.apiUrl = this.baseUrl + endpoint;
    }
  },
  getApiUrl: function() {
    return this.apiUrl;
  },
  setParams: function (params) {
    if (typeof(params) == "object") {
      this.apiUrl += "?" + this.parseParams(params);
    }
  },
  parseParams: function(params) {
    let parseParamsObject = function(object, prefix) {
      let result = "";
      Object.keys(object).forEach(key => {
        const value = object[key];
        if (typeof(value) == "object") {
          if (prefix == "") {
            result += parseParamsObject(value, key);
          } else {
            result += parseParamsObject(value,prefix + '[' + key + ']');
          }
        } else {
          if (prefix != "") {
            result += prefix + '[' + key + ']=' + value;
          } else {
            result += key + '=' + value;
          }
          result += '&';
        }
      });
      return result;
    };
    let result = parseParamsObject(params, '');
    result = result.replace(/&$/,'');
    return encodeURI(result);
  },
  callApi: function(_callback, inputData, url) {
    let data = {
      method: 'GET',
      cache: 'no-cache'
    };
    inputData = inputData || {};
    url = url || this.getApiUrl();
    data = Object.assign(data, inputData);
    fetch(url, data).then(response => {
      if (response.status === 200) {
        return response.json();
      }
      return {};
    }).then(data => {
      if (typeof(_callback) != "undefined") {
        _callback(data);
      }
    }).catch(error => {
      console.error('Error:', error);
    });
  }
};

// let myApi = new DrupalApi();
// myApi.setEndpoint("node/article");
// myApi.setParams({ "sort": "created", "page": { "limit": 2, "offset": 5 }});
// myApi.callApi(function(data) {
//   console.log(data);
// });
