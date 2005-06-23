_editor_url = "../htmlarea/";
_editor_lang = "en";
document.writeln('<script type="text/javascript" src="../htmlarea/htmlarea.js"></script>');

function initDocument() {
    config = new HTMLArea.Config();
    config.toolbar = [
                [ "bold", "italic", "underline", "strikethrough", "separator",
                  "copy", "cut", "paste", "space", "undo", "redo", "separator",
                  "justifyleft", "justifycenter", "justifyright", "justifyfull", "separator",
                  "orderedlist", "unorderedlist", "outdent", "indent", "separator",
                  "forecolor", "hilitecolor", "separator",
                  "inserthorizontalrule", "createlink", "inserttable" ]
        ];
    config.width = 700;
    var editor = new HTMLArea("editor",config);
    editor.config.pageStyle = "@import url(../application.css);";
    editor.registerPlugin(DynamicCSS);
    editor.generate();

    HTMLArea.replace("editor2", config);
}

onload = function() {
    HTMLArea.loadPlugin("DynamicCSS");
    HTMLArea.init();
    HTMLArea.onload = initDocument;
}
