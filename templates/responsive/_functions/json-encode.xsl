<?xml version="1.0"?>
<xsl:stylesheet
  version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns="http://www.w3.org/1999/xhtml"
  xmlns:func="http://exslt.org/functions"
  xmlns:exsl="http://exslt.org/common"
  xmlns:papaya-fn="http://www.papaya-cms.com/ns/functions"
  extension-element-prefixes="func"
  exclude-result-prefixes="#default papaya-fn"
>

<!--
Encode a list of tags into an json object, the tag names are used as property names.
The type attribute specified how the sub elements are handles. Possible values are
"array", "number" and "boolean". Array outputs all child elements as an json array, the
tag names of the child elements are ignored in this case. "number" and "boolean" convert the value
and output it without quoting. If no type if specified the template looks for child elements and
output them as objects. If no child nodes are found the value of elements is output as string.

<name>value</name>
to
{"name":"value"}

<name type="array"><v>value 1</v><v>value 2</v></name>
to
{"name": ["value 1", "value 2"]}

-->

<xsl:import href="./javascript-escape-string.xsl" />

<func:function name="papaya-fn:json-encode">
  <xsl:param name="values"/>
  <xsl:param name="quoteChar">"</xsl:param>
  <func:result>
    <xsl:call-template name="javascript-encode-object">
      <xsl:with-param name="values" select="$values"/>
      <xsl:with-param name="quoteChar" select="$quoteChar"/>
    </xsl:call-template>
  </func:result>
</func:function>

<xsl:template name="javascript-encode-object">
  <xsl:param name="values"/>
  <xsl:param name="quoteChar">"</xsl:param>
  <xsl:variable name="list" select="exsl:node-set($values)/*"/>
  <xsl:text>{</xsl:text>
  <xsl:if test="count($list) &gt; 0">
    <xsl:for-each select="$list">
      <xsl:if test="position() &gt; 1">
        <xsl:text>,</xsl:text>
      </xsl:if>
      <xsl:choose>
        <xsl:when test="@name">
          <xsl:value-of select="papaya-fn:javascript-escape-string(@name, $quoteChar, true())"/>
        </xsl:when>
        <xsl:otherwise>
          <xsl:value-of select="papaya-fn:javascript-escape-string(local-name(), $quoteChar, true())"/>
        </xsl:otherwise>
      </xsl:choose>
      <xsl:text>:</xsl:text>
      <xsl:call-template name="json-encode-element-value">
        <xsl:with-param name="value" select="."/>
        <xsl:with-param name="quoteChar" select="$quoteChar"/>
      </xsl:call-template>
    </xsl:for-each>
  </xsl:if>
  <xsl:text>}</xsl:text>
</xsl:template>

<!--
Encode a simple list of values into an json object, each tag needs
to be unique.

<name>value</name>
to
{"name":"value"}
-->

<func:function name="papaya-fn:json-encode-array">
  <xsl:param name="values"/>
  <xsl:param name="quoteChar">"</xsl:param>
  <func:result>
    <xsl:call-template name="json-encode-array">
      <xsl:with-param name="values" select="$values"/>
      <xsl:with-param name="quoteChar" select="$quoteChar"/>
    </xsl:call-template>
  </func:result>
</func:function>

<xsl:template name="json-encode-array">
  <xsl:param name="values"/>
  <xsl:param name="quoteChar">"</xsl:param>
  <xsl:variable name="list" select="exsl:node-set($values)/*"/>
  <xsl:text>[</xsl:text>
  <xsl:if test="count($list) &gt; 0">
    <xsl:for-each select="$list">
      <xsl:if test="position() &gt; 1">
        <xsl:text>,</xsl:text>
      </xsl:if>
      <xsl:call-template name="json-encode-element-value">
        <xsl:with-param name="value" select="."/>
        <xsl:with-param name="quoteChar" select="$quoteChar"/>
      </xsl:call-template>
    </xsl:for-each>
  </xsl:if>
  <xsl:text>]</xsl:text>
</xsl:template>

<xsl:template name="json-encode-element-value">
  <xsl:param name="value"></xsl:param>
  <xsl:param name="quoteChar">"</xsl:param>
  <xsl:choose>
    <xsl:when test="@type = 'array'">
      <xsl:value-of select="papaya-fn:json-encode-array($value)"/>
    </xsl:when>
    <xsl:when test="@type = 'number'">
      <xsl:value-of select="number($value)"/>
    </xsl:when>
    <xsl:when test="@type = 'boolean'">
      <xsl:choose>
        <xsl:when test="$value = 'true'">true</xsl:when>
        <xsl:otherwise>false</xsl:otherwise>
      </xsl:choose>
    </xsl:when>
    <xsl:when test="count($value/*) &gt; 0">
      <xsl:value-of select="papaya-fn:json-encode($value)"/>
    </xsl:when>
    <xsl:otherwise>
      <xsl:value-of select="papaya-fn:javascript-escape-string($value/text(), $quoteChar, true())"/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>