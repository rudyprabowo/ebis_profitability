#!/usr/bin/env bash

while getopts m:c:a:l:w:p: flag
do
    case "${flag}" in
        m) module=${OPTARG};;
        c) controller=${OPTARG};;
        a) action=${OPTARG};;
        l) layout=${OPTARG};;
        w) watch=${OPTARG};;
        p) prod=${OPTARG};;
    esac
done

if [[ -z "${module}" ]]
then
   echo "Module can not be null"
elif [[ -z "${controller}" ]]
then
   echo "Controller can not be null"
elif [[ -z "${action}" ]]
then
   echo "Action can not be null"
elif [[ -z "${layout}" ]]
then
   echo "Layout can not be null"
else
    echo "Module: $module";
    echo "Controller: $controller";
    echo "Action: $action";
    echo "Layout: $layout";
    echo "Production: $prod";
    # exit
    VHelper=""
    Vmini=""

    if [ "${layout}" == "tailwind-topnav" ]
    then
        VHelper=",./module/Core/src/Helper/Layout/TailwindTopNav.php"
    elif [ "${layout}" == "ism-leftnav" ]
    then
        VHelper=",./module/Core/src/Helper/Layout/ISMLeftNav.php"
    elif [ "${layout}" == "Cork" ]
    then
        VHelper=",./module/Core/src/Helper/Layout/Cork.php"
    fi

    VACT=`echo ${action} | sed 's/[A-Z]/-&/g;s/^-//'`
    VACT=`echo ${VACT} | tr [:upper:] [:lower:]`
    VCont=`echo ${controller} | sed 's/[A-Z]/-&/g;s/^-//'`
    VCont=`echo ${VCont} | tr [:upper:] [:lower:]`
    VMod=`echo ${module} | sed 's/[A-Z]/-&/g;s/^-//'`
    VMod=`echo ${VMod} | tr [:upper:] [:lower:]`
    F_TAILWIND=./src/css/${layout}/${module}/${controller}/${VACT}/1_tailwind.css
    if [ ! -f "$F_TAILWIND" ]
    then
        F_TAILWIND=./src/css/${layout}.css
        if [ ! -f "$F_TAILWIND" ]
        then
            F_TAILWIND=./src/css/1_tailwind.css
        fi
    fi

    if [ -f "$F_TAILWIND" ]
    then
        F_LAYOUT=./views/templates/layout/${layout}.phtml
        if [ -f "$F_LAYOUT" ]
        then
            F_VIEW=./module/${module}/view/${VMod}/${layout}/${VCont}/${VACT}.phtml
            if [ -f "$F_VIEW" ]
            then
                if [ "${prod}" == "1" ]
                then
                    Vmini="--minify"
                fi

                if [ "${watch}" == "1" ]
                then
                    echo "npx tailwindcss -i ${F_TAILWIND} -o ./public/css/${layout}/${module}/${controller}/${VACT}/1_tailwind.css --JIT --content=\"./views/templates/layout/${layout}.phtml,./views/templates/layout/${layout}/*.phtml,./module/${module}/view/${VMod}/${layout}/${VCont}/${VACT}.phtml,./module/${module}/view/${VMod}/${layout}/${VCont}/${VACT}/*.phtml,./public/js/${layout}/${module}/${controller}/${VACT}/*.js${VHelper}\" --watch ${Vmini}"
                    npx tailwindcss -i ${F_TAILWIND} -o ./public/css/${layout}/${module}/${controller}/${VACT}/1_tailwind.css --JIT --content="./views/templates/layout/${layout}.phtml,./views/templates/layout/${layout}/*.phtml,./module/${module}/view/${VMod}/${layout}/${VCont}/${VACT}.phtml,./module/${module}/view/${VMod}/${layout}/${VCont}/${VACT}/*.phtml,./public/js/${layout}/${module}/${controller}/${VACT}/*.js${VHelper}" --watch ${Vmini}
                else
                    echo "npx tailwindcss -i ${F_TAILWIND} -o ./public/css/${layout}/${module}/${controller}/${VACT}/1_tailwind.css --JIT --content=\"./views/templates/layout/${layout}.phtml,./views/templates/layout/${layout}/*.phtml,./module/${module}/view/${VMod}/${layout}/${VCont}/${VACT}.phtml,./module/${module}/view/${VMod}/${layout}/${VCont}/${VACT}/*.phtml,./public/js/${layout}/${module}/${controller}/${VACT}/*.js${VHelper}\" ${Vmini}"
                    npx tailwindcss -i ${F_TAILWIND} -o ./public/css/${layout}/${module}/${controller}/${VACT}/1_tailwind.css --JIT --content="./views/templates/layout/${layout}.phtml,./views/templates/layout/${layout}/*.phtml,./module/${module}/view/${VMod}/${layout}/${VCont}/${VACT}.phtml,./module/${module}/view/${VMod}/${layout}/${VCont}/${VACT}/*.phtml,./public/js/${layout}/${module}/${controller}/${VACT}/*.js${VHelper}" ${Vmini}
                fi
            else
                echo "view ${module}/view/${VMod}/${layout}/${VCont}/${VACT} not exists."
            fi
        else
            echo "Layout ${layout} not exists."
        fi
    else
        echo "$FILE not exists."
    fi
fi