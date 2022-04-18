const _color = [
    "#4d7093",
    "#5eadfe",
    "#f7b63d",
    "#ff55a4",
    "#69e750",
    "#a97dd2",
    "#ef5e5e",
    "#53d4b0"
];

const _divisi = [
    "DES",
    "DBS",
    "DGS"
];

const _segment = {
    "DES": [
        "CTS",
        "MCS",
        "EMS",
        "MMS",
        "ERS",
        "HWS",
        "MLS",
        "AFS",
        "BMS",
        "TMS",
        "FMS",
        "RDS",
        "PNBS"
    ],
    "DBS": [
        "TRB",
        "CCS",
        "MCB",
        "HTB",
        "LEF"
    ],
    "DGS": [
        "GAS",
        "MPS",
        "LGS",
        "CGS"
    ]
};

const _divsegment = {
    "DES": {
        "CTS":{
            "name": "COMMERCIAL & TOURISM SERVICE",
            "cgest": "D2",
            "ba": "T946"
        },
        "MCS":{
            "name": "MEDIA & COMMUNICATION SERVICES",
            "cgest": "DB",
            "ba": "T941"
        },
        "EMS":{
            "name": "EDUCATION MANAGEMENT SERVICES",
            "cgest": "D8",
            "ba": "T952"
        },
        "MMS":{
            "name": "MANUFACTURING MGT SERVICES",
            "cgest": "D9",
            "ba": "T945"
        },
        "ERS":{
            "name": "ENERGY & RESOURCES SERVICES",
            "cgest": "D3",
            "ba": "T948"
        },
        "HWS":{
            "name": "HEALTHCARE WELFARE SERVICES",
            "cgest": "D4",
            "ba": "T944"
        },
        "MLS":{
            "name": "MARITIME & LOGISTIC SERVICES",
            "cgest": "DF",
            "ba": "T955"
        },
        "AFS":{
            "name": "AGRICULTURE & FORESTRY SERVICES",
            "cgest": "DK",
            "ba": "T940"
        },
        "BMS":{
            "name": "BANKING SOE MGT SERVICES",
            "cgest": "DC",
            "ba": "T949"
        },
        "TMS":{
            "name": "TRANSPORTATION & MANAGEMENT SERVICES",
            "cgest": "DH",
            "ba": "T957"
          },
        "FMS":{
            "name": "FINANCIAL MGT SERVICES",
            "cgest": "DD",
            "ba": "T942"
        },
        "RDS":{
            "name": "RETAIL & DISTRIBUTION SERVICES",
            "cgest": "D6",
            "ba": "T947"
        },
        "PNBS":{
            "name": "GOVERNMENT AGENCY SERVICES",
            "cgest": "G4",
            "ba": "T964"
        }
    },
    "DBS": {
        "TRB":{
            "name": "TRADING & RESOURCE BUSINESS SERVICE",
            "cgest": 81,
            "ba": "T932"
        },
        "CCS":{
            "name": "COMMERCE & COMMUNITY SERVICE",
            "cgest": 85,
            "ba": "T935"
        },
        "MCB":{
            "name": "MANUFACTURE & COMMUNICATION BUSINESS SERVICE",
            "cgest": 82,
            "ba": "T934"
        },
        "HTB":{
            "name": "HOSPITALITY & TOURISM BUSINESS SERVICE",
            "cgest": 83,
            "ba": "T933"
        },
        "LEF":{
            "name": "LOGISTIC EDUCATION & FINANCE BUSINESS SERVICE",
            "cgest": 84,
            "ba": "T937"
        }
    },
    "DGS": {
        "GAS":{
            "name": "PRIVATE & NATIONAL BANKING SERVICES",
            "cgest": "DJ",
            "ba": "T950"
        },
        "MPS":{
            "name": "MILITARY & POLICE SERVICES",
            "cgest": "G2",
            "ba": "T962"
        },
        "LGS":{
            "name": "LOCAL GOVERNMENT SERVICE",
            "cgest": "G3",
            "ba": "T963"
        },
        "CGS":{
            "name": "CENTRAL GOVERNMENT SERVICES",
            "cgest": "G1",
            "ba": "T961"
        }
    }
};