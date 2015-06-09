# rvm_css_forked
this plugin version is a fork from the original Ruud van Melick's rvm_css with CSS rules compression included before files are saved into directory.

## Compression results compared with online services

CSS rules to process:

    p * i ,  html   
    /* remove spaces */
    /* " comments have no escapes \*/
    body/* keep */ /* space */p,
    p  [ remove ~= " spaces  " ]  :nth-child( 3 + 2n )  >  b span   i  ,   div::after {
    /* comment */
    background :  url(  "  /* string */  " )   blue  !important ;
    content  :  " escapes \" allowed \\" ;
    width: calc( 100% - 3em + 5px ) ;
    margin-top : 0;
    margin-bottom : 0;
    margin-left : 10px;
    margin-right : 10px;
    }

cssminifier.com
Length 263 byte. 12 byte longer than the output of the regex minifier included.

CSSTidy 1.3
Length 286 byte. 35 byte longer than the output of the regex  minifier included.
