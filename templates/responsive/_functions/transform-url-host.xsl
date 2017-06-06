<?xml version="1.0"?>
<xsl:stylesheet
  version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:func="http://exslt.org/functions"
  xmlns:exsl="http://exslt.org/common"
  xmlns:papaya-fn="http://www.papaya-cms.com/ns/functions"
  extension-element-prefixes="func"
  exclude-result-prefixes="papaya-fn"
>

<xsl:import href="transform-url-absolute.xsl"/>

<xsl:param name="PAGE_BASE_URL" />

<func:function name="papaya-fn:transform-url-host">
  <xsl:param name="url"></xsl:param>
  <xsl:param name="host"></xsl:param>
  <xsl:param name="onlyIfCurrentDomain" select="false()"/>
  <func:result>
    <xsl:call-template name="transform-url-host">
      <xsl:with-param name="url" select="$url"/>
      <xsl:with-param name="host" select="$host"/>
      <xsl:with-param name="onlyIfCurrentDomain" select="$onlyIfCurrentDomain"/>
    </xsl:call-template>
  </func:result>
</func:function>

<xsl:template name="transform-url-host">
  <xsl:param name="url"></xsl:param>
  <xsl:param name="host"></xsl:param>
  <xsl:param name="onlyIfCurrentDomain" select="false()"/>
  <xsl:variable name="absoluteUrl" select="papaya-fn:transform-url-absolute($url)" />
  <xsl:choose>
    <xsl:when test="not($onlyIfCurrentDomain) or starts-with($absoluteUrl, $PAGE_BASE_URL)">
      <xsl:variable name="scheme" select="concat(substring-before($absoluteUrl, '://'), '://')"/>
      <xsl:variable name="urlPath" select="substring-after(substring-after($absoluteUrl, '://'), '/')"/>
      <xsl:value-of select="concat($scheme, $host, '/', $urlPath)"/>
    </xsl:when>
    <xsl:otherwise>
      <xsl:value-of select="$absoluteUrl"/>
    </xsl:otherwise>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>