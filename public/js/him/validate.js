const MY_VALIDATE = {
  alphanumSpaceCheck: function (value, options, key, attributes) {
    let ret = undefined;
    // console.log(value, options, key, attributes);

    if (value !== null && value !== undefined && value!=="") {
      let tmp = validate.single(value, {
        type: "string",
        format: {
          pattern: "[a-zA-Z0-9 ]+",
          flags: "i",
          message: "can only contain alphanumeric and whitespace"
        }
      });
      // console.log(tmp);
      tmp = _.compact(tmp);
      ret = _.concat(ret, tmp);
    }

    ret = _.compact(ret);
    return ret;
  },
  alphanumCheck: function (value, options, key, attributes) {
    let ret = undefined;
    // console.log(value, options, key, attributes);

    if (value !== null && value !== undefined && value!=="") {
      let tmp = validate.single(value, {
        type: "string",
        format: {
          pattern: "[a-zA-Z0-9]+",
          flags: "i",
          message: "can only contain alphanumeric"
        }
      });
      // console.log(tmp);
      tmp = _.compact(tmp);
      ret = _.concat(ret, tmp);
    }

    ret = _.compact(ret);
    return ret;
  },
  numericCheck: function (value, options, key, attributes) {
    let ret = undefined;
    // console.log(value, options, key, attributes);

    if (value !== null && value !== undefined && value!=="") {
      let tmp = validate.single(value, {
        type: "string",
        format: {
          pattern: "[0-9]+",
          flags: "i",
          message: "can only contain numeric/digit"
        }
      });
      // console.log(tmp);
      tmp = _.compact(tmp);
      ret = _.concat(ret, tmp);
    }

    ret = _.compact(ret);
    return ret;
  },
  intArrayCheck: function (value, options, key, attributes) {
    let ret = undefined;
    // console.log(value, options, key, attributes);

    if (value !== null && value !== undefined && value.length > 0) {
      for (let idx = 0; idx < value.length; idx++) {
        const el = value[idx];
        let tmp = validate.single(value, {
          presence: { allowEmpty: false },
          numericality: { onlyInteger: true, greaterThan: 0, message: "must be a valid id" }
        });
        tmp = _.compact(tmp);
        // console.log(tmp);
        if (tmp.length > 0) {
          ret = _.concat(ret, tmp);
          break;
        }
      }
      // console.log(tmp);
    }

    ret = _.compact(ret);
    return ret;
  },
  isJSON: function (value, options, key, attributes) {
    let ret = undefined;
    // console.log(value, options, key, attributes);

    if (value !== null && value !== undefined && value!=="") {
      let tmp = [];
      try {
        JSON.parse(value);
      } catch (e) {
        tmp.push("must be valid JSON format")
      }
      // console.log(tmp);
      tmp = _.compact(tmp);
      ret = _.concat(ret, tmp);
    }

    ret = _.compact(ret);
    return ret;
  },
  isURLPath: function (value, options, key, attributes) {
    let ret = undefined;
    // console.log(value, options, key, attributes);

    if (value !== null && value !== undefined && value!=="") {
      let tmp = [];
      if (!_.startsWith(value, 'http') && !_.startsWith(value, '/')) {
        tmp.push("must be valid URL Path")
      }
      // console.log(tmp);
      tmp = _.compact(tmp);
      ret = _.concat(ret, tmp);
    }

    ret = _.compact(ret);
    return ret;
  },
};