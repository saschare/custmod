/**
 * jQuery function for hightlighting searchterms on clientside
 * @version 1.0.0
 * @author Thaya Kareeson
 * @copyright Copyright &copy; April 10th, 2009, http://weblogtoolscollection.com/archives/2009/04/10/how-to-highlight-search-terms-with-jquery/
 * @modified Conrad Leu, 2011, Mereo GmbH
 *
 * @todo replacement of * etc seems not to work
 **/
jQuery.fn.extend({
	highlight: function(search, insensitive, hls_class){
		var regex = new RegExp("(<[^>]*>)|(\\b"+ search.replace(/([-.*+?^${}()|[\]\/\\])/g,"\\$1") +")", insensitive ? "ig" : "g");
		return this.html(this.html().replace(regex, function(a, b, c){
			return (a.charAt(0) == "<") ? a : "<span class=\""+ hls_class +"\" title=\"Ihr Suchbegriff\">" + c + "</span>";
		}));
	}
});