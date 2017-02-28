<?php
namespace figdice\classes;

class TagFigTrans extends ViewElementTag
{
    const TAGNAME = 'trans';

    protected function doSpecific(Context $context)
    {
        return $this->fig_trans($context);
    }
    /**
     * Translates a caption given its key and dictionary name.
     * @param Context $context
     * @return string
     */
    private function fig_trans(Context $context) {

        //If a @source attribute is specified, and is equal to
        //the view's target language, then don't bother translating:
        //just render the contents.
        $source = $this->getAttribute('source', null);

        //The $key is also needed in logging below, even if
        //source = view's language, in case of missing value,
        //so this is a good time to read it.
        $key = $this->getAttribute('key', null);
        $dictionaryName = $this->getAttribute('dict', null);

        // Do we have a dictionary ?
        $dictionary = $context->getDictionary($dictionaryName);
        // Maybe not, but at this stage it is not important, I only need
        // to know its source
        $dicSource = ($dictionary ? $dictionary->getSource() : null);
        if (

            ( (null == $source) &&	//no source on the trans tag
                ($dicSource == $context->getView()->getLanguage()) )
            ||
            ($source == $context->getView()->getLanguage()) ) {
            $context->pushDoNotRenderFigParams();
            $value = $this->renderChildren($context /*Do not render fig:param immediate children */);
            $context->popDoNotRenderFigParams();
        }

        else {
            //Cross-language dictionary mechanism:

            if(null == $key) {
                //Missing @key attribute : consider the text contents as the key.
                //throw new SyntaxErrorException($this->getCurrentFile()->getFilename(), $this->xmlLineNumber, $this->name, 'Missing @key attribute.');
                $key = $this->renderChildren($context);
            }
            //Ask current context to translate key:

            $value = $context->translate($key, $dictionaryName);
        }

        //Fetch the parameters specified as immediate children
        //of the macro call : <fig:param name="" value=""/>
        //TODO: Currently, the <fig:param> of a macro call cannot hold any fig:cond or fig:case conditions.
        $arguments = array();
        foreach ($this->children as $child) {
            if($child instanceof ViewElementTag) {
                if($child->name == $context->view->figNamespace . 'param') {
                    //If param is specified with an immediate value="" attribute :
                    if(isset($child->attributes['value'])) {
                        $arguments[$child->attributes['name']] = $this->evaluate($context, $child->attributes['value']);
                    }
                    //otherwise, the actual value is not scalar but is
                    //a nodeset in itself. Let's pre-render it and use it as text for the argument.
                    else {
                        $arguments[$child->attributes['name']] = $child->render($context);
                    }
                }
            }
        }

        //We must now perform the replacements of the parameters of the translation,
        //which are written in the shape : {paramName}
        //and are specified as extra attributes of the fig:trans tag, or child fig:param tags
        //(fig:params override inline attributes).
        $matches = array();
        while(preg_match('/{([^}]+)}/', $value, $matches)) {
            $attributeName = $matches[1];
            //If there is a corresponding fig:param, use it:
            if(array_key_exists($attributeName, $arguments)) {
                $attributeValue = $arguments[$attributeName];
            }
            //Otherwise, use the inline attribute.
            else {
                $attributeValue = $this->evalAttribute($context, $attributeName);
            }
            $value = str_replace('{' . $attributeName . '}', $attributeValue, $value);
        }

        //If the translated value is empty (ie. we did find an entry in the proper dictionary file,
        //but this entry has an empty value), it means that the entry remains to be translated by the person in charge.
        //So in the meantime we output the key.
        if($value == '') {
            $value = $key;
            // TODO: One might want to be notified, either by an exception or another mechanism (Context logging?).
        }

        return $value;
    }
}
