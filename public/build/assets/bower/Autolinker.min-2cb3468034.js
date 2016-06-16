/*!
 * Autolinker.js
 * 0.24.1
 *
 * Copyright(c) 2016 Gregory Jacobs <greg@greg-jacobs.com>
 * MIT License
 *
 * https://github.com/gregjacobs/Autolinker.js
 */
!function(t,e){"function"==typeof define&&define.amd?define([],e):"object"==typeof exports?module.exports=e():t.Autolinker=e()}(this,function(){var t=function(t){t=t||{},this.urls=this.normalizeUrlsCfg(t.urls),this.email="boolean"==typeof t.email?t.email:!0,this.twitter="boolean"==typeof t.twitter?t.twitter:!0,this.phone="boolean"==typeof t.phone?t.phone:!0,this.hashtag=t.hashtag||!1,this.newWindow="boolean"==typeof t.newWindow?t.newWindow:!0,this.stripPrefix="boolean"==typeof t.stripPrefix?t.stripPrefix:!0;var e=this.hashtag;if(e!==!1&&"twitter"!==e&&"facebook"!==e&&"instagram"!==e)throw new Error("invalid `hashtag` cfg - see docs");this.truncate=this.normalizeTruncateCfg(t.truncate),this.className=t.className||"",this.replaceFn=t.replaceFn||null,this.htmlParser=null,this.matchers=null,this.tagBuilder=null};return t.prototype={constructor:t,normalizeUrlsCfg:function(t){return null==t&&(t=!0),"boolean"==typeof t?{schemeMatches:t,wwwMatches:t,tldMatches:t}:{schemeMatches:"boolean"==typeof t.schemeMatches?t.schemeMatches:!0,wwwMatches:"boolean"==typeof t.wwwMatches?t.wwwMatches:!0,tldMatches:"boolean"==typeof t.tldMatches?t.tldMatches:!0}},normalizeTruncateCfg:function(e){return"number"==typeof e?{length:e,location:"end"}:t.Util.defaults(e||{},{length:Number.POSITIVE_INFINITY,location:"end"})},parse:function(t){for(var e=this.getHtmlParser(),r=e.parse(t),n=0,s=[],i=0,a=r.length;a>i;i++){var o=r[i],h=o.getType();if("element"===h&&"a"===o.getTagName())o.isClosing()?n=Math.max(n-1,0):n++;else if("text"===h&&0===n){var c=this.parseText(o.getText(),o.getOffset());s.push.apply(s,c)}}return s=this.compactMatches(s),this.hashtag||(s=s.filter(function(t){return"hashtag"!==t.getType()})),this.email||(s=s.filter(function(t){return"email"!==t.getType()})),this.phone||(s=s.filter(function(t){return"phone"!==t.getType()})),this.twitter||(s=s.filter(function(t){return"twitter"!==t.getType()})),this.urls.schemeMatches||(s=s.filter(function(t){return"url"!==t.getType()||"scheme"!==t.getUrlMatchType()})),this.urls.wwwMatches||(s=s.filter(function(t){return"url"!==t.getType()||"www"!==t.getUrlMatchType()})),this.urls.tldMatches||(s=s.filter(function(t){return"url"!==t.getType()||"tld"!==t.getUrlMatchType()})),s},compactMatches:function(t){t.sort(function(t,e){return t.getOffset()-e.getOffset()});for(var e=0;e<t.length-1;e++)for(var r=t[e],n=r.getOffset()+r.getMatchedText().length;e+1<t.length&&t[e+1].getOffset()<=n;)t.splice(e+1,1);return t},parseText:function(t,e){e=e||0;for(var r=this.getMatchers(),n=[],s=0,i=r.length;i>s;s++){for(var a=r[s].parseMatches(t),o=0,h=a.length;h>o;o++)a[o].setOffset(e+a[o].getOffset());n.push.apply(n,a)}return n},link:function(t){if(!t)return"";for(var e=this.parse(t),r=[],n=0,s=0,i=e.length;i>s;s++){var a=e[s];r.push(t.substring(n,a.getOffset())),r.push(this.createMatchReturnVal(a)),n=a.getOffset()+a.getMatchedText().length}return r.push(t.substring(n)),r.join("")},createMatchReturnVal:function(e){var r;if(this.replaceFn&&(r=this.replaceFn.call(this,this,e)),"string"==typeof r)return r;if(r===!1)return e.getMatchedText();if(r instanceof t.HtmlTag)return r.toAnchorString();var n=this.getTagBuilder(),s=n.build(e);return s.toAnchorString()},getHtmlParser:function(){var e=this.htmlParser;return e||(e=this.htmlParser=new t.htmlParser.HtmlParser),e},getMatchers:function(){if(this.matchers)return this.matchers;var e=t.matcher,r=[new e.Hashtag({serviceName:this.hashtag}),new e.Email,new e.Phone,new e.Twitter,new e.Url({stripPrefix:this.stripPrefix})];return this.matchers=r},getTagBuilder:function(){var e=this.tagBuilder;return e||(e=this.tagBuilder=new t.AnchorTagBuilder({newWindow:this.newWindow,truncate:this.truncate,className:this.className})),e}},t.link=function(e,r){var n=new t(r);return n.link(e)},t.match={},t.matcher={},t.htmlParser={},t.truncate={},t.Util={abstractMethod:function(){throw"abstract"},trimRegex:/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g,assign:function(t,e){for(var r in e)e.hasOwnProperty(r)&&(t[r]=e[r]);return t},defaults:function(t,e){for(var r in e)e.hasOwnProperty(r)&&void 0===t[r]&&(t[r]=e[r]);return t},extend:function(e,r){var n=e.prototype,s=function(){};s.prototype=n;var i;i=r.hasOwnProperty("constructor")?r.constructor:function(){n.constructor.apply(this,arguments)};var a=i.prototype=new s;return a.constructor=i,a.superclass=n,delete r.constructor,t.Util.assign(a,r),i},ellipsis:function(t,e,r){return t.length>e&&(r=null==r?"..":r,t=t.substring(0,e-r.length)+r),t},indexOf:function(t,e){if(Array.prototype.indexOf)return t.indexOf(e);for(var r=0,n=t.length;n>r;r++)if(t[r]===e)return r;return-1},splitAndCapture:function(t,e){for(var r,n=[],s=0;r=e.exec(t);)n.push(t.substring(s,r.index)),n.push(r[0]),s=r.index+r[0].length;return n.push(t.substring(s)),n},trim:function(t){return t.replace(this.trimRegex,"")}},t.HtmlTag=t.Util.extend(Object,{whitespaceRegex:/\s+/,constructor:function(e){t.Util.assign(this,e),this.innerHtml=this.innerHtml||this.innerHTML},setTagName:function(t){return this.tagName=t,this},getTagName:function(){return this.tagName||""},setAttr:function(t,e){var r=this.getAttrs();return r[t]=e,this},getAttr:function(t){return this.getAttrs()[t]},setAttrs:function(e){var r=this.getAttrs();return t.Util.assign(r,e),this},getAttrs:function(){return this.attrs||(this.attrs={})},setClass:function(t){return this.setAttr("class",t)},addClass:function(e){for(var r,n=this.getClass(),s=this.whitespaceRegex,i=t.Util.indexOf,a=n?n.split(s):[],o=e.split(s);r=o.shift();)-1===i(a,r)&&a.push(r);return this.getAttrs()["class"]=a.join(" "),this},removeClass:function(e){for(var r,n=this.getClass(),s=this.whitespaceRegex,i=t.Util.indexOf,a=n?n.split(s):[],o=e.split(s);a.length&&(r=o.shift());){var h=i(a,r);-1!==h&&a.splice(h,1)}return this.getAttrs()["class"]=a.join(" "),this},getClass:function(){return this.getAttrs()["class"]||""},hasClass:function(t){return-1!==(" "+this.getClass()+" ").indexOf(" "+t+" ")},setInnerHtml:function(t){return this.innerHtml=t,this},getInnerHtml:function(){return this.innerHtml||""},toAnchorString:function(){var t=this.getTagName(),e=this.buildAttrsStr();return e=e?" "+e:"",["<",t,e,">",this.getInnerHtml(),"</",t,">"].join("")},buildAttrsStr:function(){if(!this.attrs)return"";var t=this.getAttrs(),e=[];for(var r in t)t.hasOwnProperty(r)&&e.push(r+'="'+t[r]+'"');return e.join(" ")}}),t.RegexLib=function(){var t="A-Za-zªµºÀ-ÖØ-öø-ˁˆ-ˑˠ-ˤˬˮͰ-ʹͶͷͺ-ͽͿΆΈ-ΊΌΎ-ΡΣ-ϵϷ-ҁҊ-ԯԱ-Ֆՙա-ևא-תװ-ײؠ-يٮٯٱ-ۓەۥۦۮۯۺ-ۼۿܐܒ-ܯݍ-ޥޱߊ-ߪߴߵߺࠀ-ࠕࠚࠤࠨࡀ-ࡘࢠ-ࢴऄ-हऽॐक़-ॡॱ-ঀঅ-ঌএঐও-নপ-রলশ-হঽৎড়ঢ়য়-ৡৰৱਅ-ਊਏਐਓ-ਨਪ-ਰਲਲ਼ਵਸ਼ਸਹਖ਼-ੜਫ਼ੲ-ੴઅ-ઍએ-ઑઓ-નપ-રલળવ-હઽૐૠૡૹଅ-ଌଏଐଓ-ନପ-ରଲଳଵ-ହଽଡ଼ଢ଼ୟ-ୡୱஃஅ-ஊஎ-ஐஒ-கஙசஜஞடணதந-பம-ஹௐఅ-ఌఎ-ఐఒ-నప-హఽౘ-ౚౠౡಅ-ಌಎ-ಐಒ-ನಪ-ಳವ-ಹಽೞೠೡೱೲഅ-ഌഎ-ഐഒ-ഺഽൎൟ-ൡൺ-ൿඅ-ඖක-නඳ-රලව-ෆก-ะาำเ-ๆກຂຄງຈຊຍດ-ທນ-ຟມ-ຣລວສຫອ-ະາຳຽເ-ໄໆໜ-ໟༀཀ-ཇཉ-ཬྈ-ྌက-ဪဿၐ-ၕၚ-ၝၡၥၦၮ-ၰၵ-ႁႎႠ-ჅჇჍა-ჺჼ-ቈቊ-ቍቐ-ቖቘቚ-ቝበ-ኈኊ-ኍነ-ኰኲ-ኵኸ-ኾዀዂ-ዅወ-ዖዘ-ጐጒ-ጕጘ-ፚᎀ-ᎏᎠ-Ᏽᏸ-ᏽᐁ-ᙬᙯ-ᙿᚁ-ᚚᚠ-ᛪᛱ-ᛸᜀ-ᜌᜎ-ᜑᜠ-ᜱᝀ-ᝑᝠ-ᝬᝮ-ᝰក-ឳៗៜᠠ-ᡷᢀ-ᢨᢪᢰ-ᣵᤀ-ᤞᥐ-ᥭᥰ-ᥴᦀ-ᦫᦰ-ᧉᨀ-ᨖᨠ-ᩔᪧᬅ-ᬳᭅ-ᭋᮃ-ᮠᮮᮯᮺ-ᯥᰀ-ᰣᱍ-ᱏᱚ-ᱽᳩ-ᳬᳮ-ᳱᳵᳶᴀ-ᶿḀ-ἕἘ-Ἕἠ-ὅὈ-Ὅὐ-ὗὙὛὝὟ-ώᾀ-ᾴᾶ-ᾼιῂ-ῄῆ-ῌῐ-ΐῖ-Ίῠ-Ῥῲ-ῴῶ-ῼⁱⁿₐ-ₜℂℇℊ-ℓℕℙ-ℝℤΩℨK-ℭℯ-ℹℼ-ℿⅅ-ⅉⅎↃↄⰀ-Ⱞⰰ-ⱞⱠ-ⳤⳫ-ⳮⳲⳳⴀ-ⴥⴧⴭⴰ-ⵧⵯⶀ-ⶖⶠ-ⶦⶨ-ⶮⶰ-ⶶⶸ-ⶾⷀ-ⷆⷈ-ⷎⷐ-ⷖⷘ-ⷞⸯ々〆〱-〵〻〼ぁ-ゖゝ-ゟァ-ヺー-ヿㄅ-ㄭㄱ-ㆎㆠ-ㆺㇰ-ㇿ㐀-䶵一-鿕ꀀ-ꒌꓐ-ꓽꔀ-ꘌꘐ-ꘟꘪꘫꙀ-ꙮꙿ-ꚝꚠ-ꛥꜗ-ꜟꜢ-ꞈꞋ-ꞭꞰ-ꞷꟷ-ꠁꠃ-ꠅꠇ-ꠊꠌ-ꠢꡀ-ꡳꢂ-ꢳꣲ-ꣷꣻꣽꤊ-ꤥꤰ-ꥆꥠ-ꥼꦄ-ꦲꧏꧠ-ꧤꧦ-ꧯꧺ-ꧾꨀ-ꨨꩀ-ꩂꩄ-ꩋꩠ-ꩶꩺꩾ-ꪯꪱꪵꪶꪹ-ꪽꫀꫂꫛ-ꫝꫠ-ꫪꫲ-ꫴꬁ-ꬆꬉ-ꬎꬑ-ꬖꬠ-ꬦꬨ-ꬮꬰ-ꭚꭜ-ꭥꭰ-ꯢ가-힣ힰ-ퟆퟋ-ퟻ豈-舘並-龎ﬀ-ﬆﬓ-ﬗיִײַ-ﬨשׁ-זּטּ-לּמּנּסּףּפּצּ-ﮱﯓ-ﴽﵐ-ﶏﶒ-ﷇﷰ-ﷻﹰ-ﹴﹶ-ﻼＡ-Ｚａ-ｚｦ-ﾾￂ-ￇￊ-ￏￒ-ￗￚ-ￜ",e="0-9٠-٩۰-۹߀-߉०-९০-৯੦-੯૦-૯୦-୯௦-௯౦-౯೦-೯൦-൯෦-෯๐-๙໐-໙༠-༩၀-၉႐-႙០-៩᠐-᠙᥆-᥏᧐-᧙᪀-᪉᪐-᪙᭐-᭙᮰-᮹᱀-᱉᱐-᱙꘠-꘩꣐-꣙꤀-꤉꧐-꧙꧰-꧹꩐-꩙꯰-꯹０-９",r=t+e,n=new RegExp("["+r+".\\-]*["+r+"\\-]"),s=/(?:international|construction|contractors|enterprises|photography|productions|foundation|immobilien|industries|management|properties|technology|christmas|community|directory|education|equipment|institute|marketing|solutions|vacations|bargains|boutique|builders|catering|cleaning|clothing|computer|democrat|diamonds|graphics|holdings|lighting|partners|plumbing|supplies|training|ventures|academy|careers|company|cruises|domains|exposed|flights|florist|gallery|guitars|holiday|kitchen|neustar|okinawa|recipes|rentals|reviews|shiksha|singles|support|systems|agency|berlin|camera|center|coffee|condos|dating|estate|events|expert|futbol|kaufen|luxury|maison|monash|museum|nagoya|photos|repair|report|social|supply|tattoo|tienda|travel|viajes|villas|vision|voting|voyage|actor|build|cards|cheap|codes|dance|email|glass|house|mango|ninja|parts|photo|press|shoes|solar|today|tokyo|tools|watch|works|aero|arpa|asia|best|bike|blue|buzz|camp|club|cool|coop|farm|fish|gift|guru|info|jobs|kiwi|kred|land|limo|link|menu|mobi|moda|name|pics|pink|post|qpon|rich|ruhr|sexy|tips|vote|voto|wang|wien|wiki|zone|bar|bid|biz|cab|cat|ceo|com|edu|gov|int|kim|mil|net|onl|org|pro|pub|red|tel|uno|wed|xxx|xyz|ac|ad|ae|af|ag|ai|al|am|an|ao|aq|ar|as|at|au|aw|ax|az|ba|bb|bd|be|bf|bg|bh|bi|bj|bm|bn|bo|br|bs|bt|bv|bw|by|bz|ca|cc|cd|cf|cg|ch|ci|ck|cl|cm|cn|co|cr|cu|cv|cw|cx|cy|cz|de|dj|dk|dm|do|dz|ec|ee|eg|er|es|et|eu|fi|fj|fk|fm|fo|fr|ga|gb|gd|ge|gf|gg|gh|gi|gl|gm|gn|gp|gq|gr|gs|gt|gu|gw|gy|hk|hm|hn|hr|ht|hu|id|ie|il|im|in|io|iq|ir|is|it|je|jm|jo|jp|ke|kg|kh|ki|km|kn|kp|kr|kw|ky|kz|la|lb|lc|li|lk|lr|ls|lt|lu|lv|ly|ma|mc|md|me|mg|mh|mk|ml|mm|mn|mo|mp|mq|mr|ms|mt|mu|mv|mw|mx|my|mz|na|nc|ne|nf|ng|ni|nl|no|np|nr|nu|nz|om|pa|pe|pf|pg|ph|pk|pl|pm|pn|pr|ps|pt|pw|py|qa|re|ro|rs|ru|rw|sa|sb|sc|sd|se|sg|sh|si|sj|sk|sl|sm|sn|so|sr|st|su|sv|sx|sy|sz|tc|td|tf|tg|th|tj|tk|tl|tm|tn|to|tp|tr|tt|tv|tw|tz|ua|ug|uk|us|uy|uz|va|vc|ve|vg|vi|vn|vu|wf|ws|ye|yt|za|zm|zw)\b/;return{alphaNumericCharsStr:r,domainNameRegex:n,tldRegex:s}}(),t.AnchorTagBuilder=t.Util.extend(Object,{constructor:function(e){t.Util.assign(this,e)},build:function(e){return new t.HtmlTag({tagName:"a",attrs:this.createAttrs(e.getType(),e.getAnchorHref()),innerHtml:this.processAnchorText(e.getAnchorText())})},createAttrs:function(t,e){var r={href:e},n=this.createCssClass(t);return n&&(r["class"]=n),this.newWindow&&(r.target="_blank"),r},createCssClass:function(t){var e=this.className;return e?e+" "+e+"-"+t:""},processAnchorText:function(t){return t=this.doTruncate(t)},doTruncate:function(e){var r=this.truncate;if(!r)return e;var n=r.length,s=r.location;return"smart"===s?t.truncate.TruncateSmart(e,n,".."):"middle"===s?t.truncate.TruncateMiddle(e,n,".."):t.truncate.TruncateEnd(e,n,"..")}}),t.htmlParser.HtmlParser=t.Util.extend(Object,{htmlRegex:function(){var t=/!--([\s\S]+?)--/,e=/[0-9a-zA-Z][0-9a-zA-Z:]*/,r=/[^\s\0"'>\/=\x01-\x1F\x7F]+/,n=/(?:"[^"]*?"|'[^']*?'|[^'"=<>`\s]+)/,s=r.source+"(?:\\s*=\\s*"+n.source+")?";return new RegExp(["(?:","<(!DOCTYPE)","(?:","\\s+","(?:",s,"|",n.source+")",")*",">",")","|","(?:","<(/)?","(?:",t.source,"|","(?:","("+e.source+")","(?:","\\s*",s,")*","\\s*/?",")",")",">",")"].join(""),"gi")}(),htmlCharacterEntitiesRegex:/(&nbsp;|&#160;|&lt;|&#60;|&gt;|&#62;|&quot;|&#34;|&#39;)/gi,parse:function(t){for(var e,r,n=this.htmlRegex,s=0,i=[];null!==(e=n.exec(t));){var a=e[0],o=e[3],h=e[1]||e[4],c=!!e[2],u=e.index,l=t.substring(s,u);l&&(r=this.parseTextAndEntityNodes(s,l),i.push.apply(i,r)),o?i.push(this.createCommentNode(u,a,o)):i.push(this.createElementNode(u,a,h,c)),s=u+a.length}if(s<t.length){var g=t.substring(s);g&&(r=this.parseTextAndEntityNodes(s,g),i.push.apply(i,r))}return i},parseTextAndEntityNodes:function(e,r){for(var n=[],s=t.Util.splitAndCapture(r,this.htmlCharacterEntitiesRegex),i=0,a=s.length;a>i;i+=2){var o=s[i],h=s[i+1];o&&(n.push(this.createTextNode(e,o)),e+=o.length),h&&(n.push(this.createEntityNode(e,h)),e+=h.length)}return n},createCommentNode:function(e,r,n){return new t.htmlParser.CommentNode({offset:e,text:r,comment:t.Util.trim(n)})},createElementNode:function(e,r,n,s){return new t.htmlParser.ElementNode({offset:e,text:r,tagName:n.toLowerCase(),closing:s})},createEntityNode:function(e,r){return new t.htmlParser.EntityNode({offset:e,text:r})},createTextNode:function(e,r){return new t.htmlParser.TextNode({offset:e,text:r})}}),t.htmlParser.HtmlNode=t.Util.extend(Object,{offset:void 0,text:void 0,constructor:function(e){t.Util.assign(this,e)},getType:t.Util.abstractMethod,getOffset:function(){return this.offset},getText:function(){return this.text}}),t.htmlParser.CommentNode=t.Util.extend(t.htmlParser.HtmlNode,{comment:"",getType:function(){return"comment"},getComment:function(){return this.comment}}),t.htmlParser.ElementNode=t.Util.extend(t.htmlParser.HtmlNode,{tagName:"",closing:!1,getType:function(){return"element"},getTagName:function(){return this.tagName},isClosing:function(){return this.closing}}),t.htmlParser.EntityNode=t.Util.extend(t.htmlParser.HtmlNode,{getType:function(){return"entity"}}),t.htmlParser.TextNode=t.Util.extend(t.htmlParser.HtmlNode,{getType:function(){return"text"}}),t.match.Match=t.Util.extend(Object,{constructor:function(t,e){this.matchedText=t,this.offset=e},getType:t.Util.abstractMethod,getMatchedText:function(){return this.matchedText},setOffset:function(t){this.offset=t},getOffset:function(){return this.offset},getAnchorHref:t.Util.abstractMethod,getAnchorText:t.Util.abstractMethod}),t.match.Email=t.Util.extend(t.match.Match,{constructor:function(e,r,n){t.match.Match.prototype.constructor.call(this,e,r),this.email=n},getType:function(){return"email"},getEmail:function(){return this.email},getAnchorHref:function(){return"mailto:"+this.email},getAnchorText:function(){return this.email}}),t.match.Hashtag=t.Util.extend(t.match.Match,{constructor:function(e,r,n,s){t.match.Match.prototype.constructor.call(this,e,r),this.serviceName=n,this.hashtag=s},getType:function(){return"hashtag"},getServiceName:function(){return this.serviceName},getHashtag:function(){return this.hashtag},getAnchorHref:function(){var t=this.serviceName,e=this.hashtag;switch(t){case"twitter":return"https://twitter.com/hashtag/"+e;case"facebook":return"https://www.facebook.com/hashtag/"+e;case"instagram":return"https://instagram.com/explore/tags/"+e;default:throw new Error("Unknown service name to point hashtag to: ",t)}},getAnchorText:function(){return"#"+this.hashtag}}),t.match.Phone=t.Util.extend(t.match.Match,{constructor:function(e,r,n,s){t.match.Match.prototype.constructor.call(this,e,r),this.number=n,this.plusSign=s},getType:function(){return"phone"},getNumber:function(){return this.number},getAnchorHref:function(){return"tel:"+(this.plusSign?"+":"")+this.number},getAnchorText:function(){return this.matchedText}}),t.match.Twitter=t.Util.extend(t.match.Match,{constructor:function(e,r,n){t.match.Match.prototype.constructor.call(this,e,r),this.twitterHandle=n},getType:function(){return"twitter"},getTwitterHandle:function(){return this.twitterHandle},getAnchorHref:function(){return"https://twitter.com/"+this.twitterHandle},getAnchorText:function(){return"@"+this.twitterHandle}}),t.match.Url=t.Util.extend(t.match.Match,{constructor:function(e,r,n,s,i,a,o){t.match.Match.prototype.constructor.call(this,e,r),this.urlMatchType=s,this.url=n,this.protocolUrlMatch=i,this.protocolRelativeMatch=a,this.stripPrefix=o},urlPrefixRegex:/^(https?:\/\/)?(www\.)?/i,protocolRelativeRegex:/^\/\//,protocolPrepended:!1,getType:function(){return"url"},getUrlMatchType:function(){return this.urlMatchType},getUrl:function(){var t=this.url;return this.protocolRelativeMatch||this.protocolUrlMatch||this.protocolPrepended||(t=this.url="http://"+t,this.protocolPrepended=!0),t},getAnchorHref:function(){var t=this.getUrl();return t.replace(/&amp;/g,"&")},getAnchorText:function(){var t=this.getMatchedText();return this.protocolRelativeMatch&&(t=this.stripProtocolRelativePrefix(t)),this.stripPrefix&&(t=this.stripUrlPrefix(t)),t=this.removeTrailingSlash(t)},stripUrlPrefix:function(t){return t.replace(this.urlPrefixRegex,"")},stripProtocolRelativePrefix:function(t){return t.replace(this.protocolRelativeRegex,"")},removeTrailingSlash:function(t){return"/"===t.charAt(t.length-1)&&(t=t.slice(0,-1)),t}}),t.matcher.Matcher=t.Util.extend(Object,{constructor:function(e){t.Util.assign(this,e)},parseMatches:t.Util.abstractMethod}),t.matcher.Email=t.Util.extend(t.matcher.Matcher,{matcherRegex:function(){var e=t.RegexLib.alphaNumericCharsStr,r=new RegExp("["+e+"\\-;:&=+$.,]+@"),n=t.RegexLib.domainNameRegex,s=t.RegexLib.tldRegex;return new RegExp([r.source,n.source,"\\.",s.source].join(""),"gi")}(),parseMatches:function(e){for(var r,n=this.matcherRegex,s=[];null!==(r=n.exec(e));){var i=r[0];s.push(new t.match.Email(i,r.index,i))}return s}}),t.matcher.Hashtag=t.Util.extend(t.matcher.Matcher,{matcherRegex:new RegExp("#[_"+t.RegexLib.alphaNumericCharsStr+"]{1,139}","g"),nonWordCharRegex:new RegExp("[^"+t.RegexLib.alphaNumericCharsStr+"]"),parseMatches:function(e){for(var r,n=this.matcherRegex,s=this.nonWordCharRegex,i=this.serviceName,a=[];null!==(r=n.exec(e));){var o=r.index,h=e.charAt(o-1);if(0===o||s.test(h)){var c=r[0],u=r[0].slice(1);a.push(new t.match.Hashtag(c,o,i,u))}}return a}}),t.matcher.Phone=t.Util.extend(t.matcher.Matcher,{matcherRegex:/(?:(\+)?\d{1,3}[-\040.])?\(?\d{3}\)?[-\040.]?\d{3}[-\040.]\d{4}/g,parseMatches:function(e){for(var r,n=this.matcherRegex,s=[];null!==(r=n.exec(e));){var i=r[0],a=i.replace(/\D/g,""),o=!!r[1];s.push(new t.match.Phone(i,r.index,a,o))}return s}}),t.matcher.Twitter=t.Util.extend(t.matcher.Matcher,{matcherRegex:new RegExp("@[_"+t.RegexLib.alphaNumericCharsStr+"]{1,20}","g"),nonWordCharRegex:new RegExp("[^"+t.RegexLib.alphaNumericCharsStr+"]"),parseMatches:function(e){for(var r,n=this.matcherRegex,s=this.nonWordCharRegex,i=[];null!==(r=n.exec(e));){var a=r.index,o=e.charAt(a-1);if(0===a||s.test(o)){var h=r[0],c=r[0].slice(1);i.push(new t.match.Twitter(h,a,c))}}return i}}),t.matcher.Url=t.Util.extend(t.matcher.Matcher,{matcherRegex:function(){var e=/(?:[A-Za-z][-.+A-Za-z0-9]*:(?![A-Za-z][-.+A-Za-z0-9]*:\/\/)(?!\d+\/?)(?:\/\/)?)/,r=/(?:www\.)/,n=t.RegexLib.domainNameRegex,s=t.RegexLib.tldRegex,i=t.RegexLib.alphaNumericCharsStr,a=new RegExp("["+i+"\\-+&@#/%=~_()|'$*\\[\\]?!:,.;]*["+i+"\\-+&@#/%=~_()|'$*\\[\\]]");return new RegExp(["(?:","(",e.source,n.source,")","|","(","(//)?",r.source,n.source,")","|","(","(//)?",n.source+"\\.",s.source,")",")","(?:"+a.source+")?"].join(""),"gi")}(),wordCharRegExp:/\w/,openParensRe:/\(/g,closeParensRe:/\)/g,parseMatches:function(e){for(var r,n=this.matcherRegex,s=this.stripPrefix,i=[];null!==(r=n.exec(e));){var a=r[0],o=r[1],h=r[2],c=r[3],u=r[5],l=r.index,g=c||u,f=e.charAt(l-1);if(t.matcher.UrlMatchValidator.isValid(a,o)&&!(l>0&&"@"===f||l>0&&g&&this.wordCharRegExp.test(f))){if(this.matchHasUnbalancedClosingParen(a))a=a.substr(0,a.length-1);else{var m=this.matchHasInvalidCharAfterTld(a,o);m>-1&&(a=a.substr(0,m))}var p=o?"scheme":h?"www":"tld",d=!!o;i.push(new t.match.Url(a,l,a,p,d,!!g,s))}}return i},matchHasUnbalancedClosingParen:function(t){var e=t.charAt(t.length-1);if(")"===e){var r=t.match(this.openParensRe),n=t.match(this.closeParensRe),s=r&&r.length||0,i=n&&n.length||0;if(i>s)return!0}return!1},matchHasInvalidCharAfterTld:function(t,e){if(!t)return-1;var r=0;e&&(r=t.indexOf(":"),t=t.slice(r));var n=/^((.?\/\/)?[A-Za-z0-9\u00C0-\u017F\.\-]*[A-Za-z0-9\u00C0-\u017F\-]\.[A-Za-z]+)/,s=n.exec(t);return null===s?-1:(r+=s[1].length,t=t.slice(s[1].length),/^[^.A-Za-z:\/?#]/.test(t)?r:-1)}}),t.matcher.UrlMatchValidator={hasFullProtocolRegex:/^[A-Za-z][-.+A-Za-z0-9]*:\/\//,uriSchemeRegex:/^[A-Za-z][-.+A-Za-z0-9]*:/,hasWordCharAfterProtocolRegex:/:[^\s]*?[A-Za-z\u00C0-\u017F]/,isValid:function(t,e){return!(e&&!this.isValidUriScheme(e)||this.urlMatchDoesNotHaveProtocolOrDot(t,e)||this.urlMatchDoesNotHaveAtLeastOneWordChar(t,e))},isValidUriScheme:function(t){var e=t.match(this.uriSchemeRegex)[0].toLowerCase();return"javascript:"!==e&&"vbscript:"!==e},urlMatchDoesNotHaveProtocolOrDot:function(t,e){return!(!t||e&&this.hasFullProtocolRegex.test(e)||-1!==t.indexOf("."))},urlMatchDoesNotHaveAtLeastOneWordChar:function(t,e){return t&&e?!this.hasWordCharAfterProtocolRegex.test(t):!1}},t.truncate.TruncateEnd=function(e,r,n){return t.Util.ellipsis(e,r,n)},t.truncate.TruncateMiddle=function(t,e,r){if(t.length<=e)return t;var n=e-r.length,s="";return n>0&&(s=t.substr(-1*Math.floor(n/2))),(t.substr(0,Math.ceil(n/2))+r+s).substr(0,e)},t.truncate.TruncateSmart=function(t,e,r){var n=function(t){var e={},r=t,n=r.match(/^([a-z]+):\/\//i);return n&&(e.scheme=n[1],r=r.substr(n[0].length)),n=r.match(/^(.*?)(?=(\?|#|\/|$))/i),n&&(e.host=n[1],r=r.substr(n[0].length)),n=r.match(/^\/(.*?)(?=(\?|#|$))/i),n&&(e.path=n[1],r=r.substr(n[0].length)),n=r.match(/^\?(.*?)(?=(#|$))/i),n&&(e.query=n[1],r=r.substr(n[0].length)),n=r.match(/^#(.*?)$/i),n&&(e.fragment=n[1]),e},s=function(t){var e="";return t.scheme&&t.host&&(e+=t.scheme+"://"),t.host&&(e+=t.host),t.path&&(e+="/"+t.path),t.query&&(e+="?"+t.query),t.fragment&&(e+="#"+t.fragment),e},i=function(t,e){var n=e/2,s=Math.ceil(n),i=-1*Math.floor(n),a="";return 0>i&&(a=t.substr(i)),t.substr(0,s)+r+a};if(t.length<=e)return t;var a=e-r.length,o=n(t);if(o.query){var h=o.query.match(/^(.*?)(?=(\?|\#))(.*?)$/i);h&&(o.query=o.query.substr(0,h[1].length),t=s(o))}if(t.length<=e)return t;if(o.host&&(o.host=o.host.replace(/^www\./,""),t=s(o)),t.length<=e)return t;var c="";if(o.host&&(c+=o.host),c.length>=a)return o.host.length==e?(o.host.substr(0,e-r.length)+r).substr(0,e):i(c,a).substr(0,e);var u="";if(o.path&&(u+="/"+o.path),o.query&&(u+="?"+o.query),u){if((c+u).length>=a){if((c+u).length==e)return(c+u).substr(0,e);var l=a-c.length;return(c+i(u,l)).substr(0,e)}c+=u}if(o.fragment){var g="#"+o.fragment;if((c+g).length>=a){if((c+g).length==e)return(c+g).substr(0,e);var f=a-c.length;return(c+i(g,f)).substr(0,e)}c+=g}if(o.scheme&&o.host){var m=o.scheme+"://";if((c+m).length<a)return(m+c).substr(0,e)}if(c.length<=e)return c;var p="";return a>0&&(p=c.substr(-1*Math.floor(a/2))),(c.substr(0,Math.ceil(a/2))+r+p).substr(0,e)},t});