<?xml version="1.0"?>
<xsl:stylesheet
  version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns="http://www.google.com/schemas/sitemap/0.84"
  xmlns:papaya-fn="http://www.papaya-cms.com/ns/functions"
  exclude-result-prefixes="papaya-fn">
<!--
  @papaya:modules content_sitemap
-->
<xsl:import href="../_functions/transform-url-absolute.xsl"/>

<xsl:output method="xml" encoding="UTF-8" standalone="yes" indent="yes" omit-xml-declaration="no" />

<xsl:template match="/page">
  <xsl:variable name="module" select="content/topic/@module"/>
  <xsl:choose>
    <xsl:when test="$module = 'content_sitemap'">
      <xsl:call-template name="module-content-sitemap">
        <xsl:with-param name="topic" select="content/topic" />
      </xsl:call-template>
    </xsl:when>
  </xsl:choose>
</xsl:template>

<xsl:template name="module-content-sitemap">
  <xsl:param name="topic" />
  <urlset>
    <xsl:call-template name="module-content-sitemap-items">
      <xsl:with-param name="items" select="$topic/sitemap/mapitem" />
    </xsl:call-template>
  </urlset>
</xsl:template>

<xsl:template name="module-content-sitemap-items">
  <xsl:param name="items" />
  <xsl:for-each select="$items">
    <xsl:call-template name="module-content-sitemap-item">
      <xsl:with-param name="item" select="."/>
    </xsl:call-template>
    <xsl:if test="mapitem">
      <xsl:call-template name="module-content-sitemap-items">
       <xsl:with-param name="items" select="mapitem" />
      </xsl:call-template>
    </xsl:if>
  </xsl:for-each>
</xsl:template>

<xsl:template name="module-content-sitemap-item">
  <xsl:param name="item" />
  <url>
    <loc><xsl:value-of select="papaya-fn:transform-url-absolute($item/@href)"/></loc>
    <lastmod><xsl:value-of select="substring($item/@lastmod,0,11)"/></lastmod>
    <changefreq><xsl:value-of select="$item/@changefreq"/></changefreq>
    <priority><xsl:value-of select="$item/@priority"/></priority>
  </url>
</xsl:template>

</xsl:stylesheet>