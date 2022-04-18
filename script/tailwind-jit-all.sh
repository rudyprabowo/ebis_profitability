#!/usr/bin/env bash

while getopts m:c:a:l: flag
do
    case "${flag}" in
        m) module=${OPTARG};;
        c) controller=${OPTARG};;
        a) action=${OPTARG};;
        l) layout=${OPTARG};;
    esac
done

TMP="*"
if [[ ! -z "${module}" ]]
then
  TMP=${module}
fi

TMP2="*"
if [[ ! -z "${layout}" ]]
then
  TMP2=${layout}
fi

TMP3="*"
if [[ ! -z "${controller}" ]]
then
  TMP3=`echo ${controller} | sed 's/[A-Z]/-&/g;s/^-//'`
  TMP3=`echo ${TMP3} | tr [:upper:] [:lower:]`
fi

for file in ./module/$TMP; do
    MODULE="$(basename "$file")"
    PMODULE=$file
    # echo $MODULE
    # echo $MODULE_PATH
    VMod=`echo ${MODULE} | sed 's/[A-Z]/-&/g;s/^-//'`
    VMod=`echo ${VMod} | tr [:upper:] [:lower:]`
    for file2 in $PMODULE/view/$VMod/$TMP2; do
      LAYOUT="$(basename "$file2")"
      PLAYOUT=$file2
      # echo $LAYOUT
      for file3 in $PLAYOUT/$TMP3; do
        VCon="$(basename "$file3")"
        VCon=( $VCon ) # without quotes
        CONTROLLER="${VCon[@]^}"
        PCONTROLLER=$file3
        # echo $CONTROLLER
        for file4 in $PCONTROLLER/*; do
          VACTION="$(basename "$file4")"
          PACTION=$file4
          # echo $VACTION
          ACTION="${VACTION/.phtml/}"
          # echo $ACTION
          echo "Module: $MODULE";
          echo "Controller: $CONTROLLER";
          echo "Action: $ACTION";
          echo "Layout: $LAYOUT";
          ./script/tailwind-jit.sh -m $MODULE -c $CONTROLLER -a $ACTION -l $LAYOUT -w 0
          echo "================================================================";
        done
      done
    done
done