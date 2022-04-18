/*
Type data dalam Javascript ada 2 yaitu :
1. Primitives : String, Number, Boolean, Null, Undefined, Symbol
2. Object
*/

var var1 = "var1";
var var2 = 2;
var var3 = -3;
var var4 = 4.4;
var var5 = -5.5;
var var6 = {};
var var7 = [];
var var8;
var var9 = null;
console.log("===TYPE Start===");
console.log("var1 ('var1'): " + typeof var1);
console.log("var2 (2): " + typeof var2);
console.log("var3 (-3): " + typeof var3);
console.log("var4 (4.4): " + typeof var4);
console.log("var5 (-5.5): " + typeof var5);
console.log("var6 ({}): " + typeof var6);
console.log("var7 ([]): " + typeof var7);
console.log("var8 : " + typeof var8);
console.log("var9 (null): " + typeof var9);
console.log("===TYPE End===");

// String Variable
var str1 = "String 1";
let str2 = "String 2";
var STR = {
  initVar: function() {
    console.log("===STR.initVar Start===");
    var str3 = "String 3";
    let str4 = "String 4";
    console.log("str1 :" + str1);
    console.log("str2 :" + str2);
    console.log("str3 :" + str3);
    console.log("str4 :" + str4);
    console.log("===STR.initVar End===");
  },
  initVar2: function() {
    //REASSIGN
    console.log("===STR.initVar2 Start===");
    var str1 = "String 1 baru";
    let str2 = "String 2 baru";
    var str3 = "String 3 baru";
    let str4 = "String 4 baru";
    console.log("str1 :" + str1);
    console.log("str2 :" + str2);
    console.log("str3 :" + str3);
    console.log("str4 :" + str4);
    console.log("===STR.initVar2 End===");
  },
  initVar3: function() {
    console.log("===STR.initVar3 Start===");
    console.log("str1 :" + str1);
    console.log("str2 :" + str2);
    // console.log("str3 :"+str3); //error not defined
    // console.log("str4 :"+str4); //error not defined
    console.log("===STR.initVar3 End===");
  }
}

STR.initVar();
STR.initVar2();
STR.initVar3();