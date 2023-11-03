window.DrupalApi = function () {};

DrupalApi.prototype = {
  baseUrl: '/jsonapi/',
  setBaseUrl(baseUrl) {
    this.baseUrl = baseUrl;
  },
  setEndpoint(endpoint) {
    if (typeof endpoint === 'string') {
      this.apiUrl = this.baseUrl + endpoint;
    }
  },
  getApiUrl() {
    return this.apiUrl;
  },
  setParams(params) {
    if (typeof params === 'object') {
      this.apiUrl += `?${this.parseParams(params)}`;
    }
  },
  parseParams(params) {
    const parseParamsObject = function (object, prefix) {
      let result = '';
      Object.keys(object).forEach((key) => {
        const value = object[key];
        if (typeof value === 'object') {
          if (prefix === '') {
            result += parseParamsObject(value, key);
          } else {
            result += parseParamsObject(value, `${prefix}[${key}]`);
          }
        } else {
          if (prefix !== '') {
            result += `${prefix}[${key}]=${value}`;
          } else {
            result += `${key}=${value}`;
          }
          result += '&';
        }
      });
      return result;
    };
    let result = parseParamsObject(params, '');
    result = result.replace(/&$/, '');
    return encodeURI(result);
  },
  callApi(_callback, inputData, url) {
    let data = {
      method: 'GET',
      cache: 'no-cache',
    };
    inputData = inputData || {};
    url = url || this.getApiUrl();
    data = Object.assign(data, inputData);
    fetch(url, data)
      .then((response) => {
        if (response.status === 200) {
          return response.json();
        }
        return {};
      })
      .then((data) => {
        if (typeof _callback !== 'undefined') {
          _callback(data);
        }
      })
      .catch((error) => {
        console.error('Error:', error);
      });
  },
};
