<?xml version="1.0"?>
<xsl:stylesheet
  version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:func="http://exslt.org/functions"
  xmlns:str="http://exslt.org/strings"
  xmlns:exsl="http://exslt.org/common"
  xmlns:papaya-fn="http://www.papaya-cms.com/ns/functions"
  extension-element-prefixes="func str exsl"
  exclude-result-prefixes="papaya-fn">
  
<xsl:import href="./conditional-comment.xsl"/>
  
<!-- theme set id if defined -->
<xsl:param name="PAGE_THEME_SET">0</xsl:param>
<!-- theme path in browser -->
<xsl:param name="PAGE_THEME_PATH" />

<!-- website version string if available -->
<xsl:param name="PAGE_WEBSITE_REVISION" />

<!-- installation in dev mode? (option in conf.inc.php) -->
<xsl:param name="PAPAYA_DBG_DEVMODE" />

<!--
  template definitions
-->
<xsl:key name="box-modules" match="/page/boxes/box" use="@module"/>

<func:function name="papaya-fn:getModuleThemeFiles">
  <xsl:param name="files"/>
  <xsl:variable name="xml">
    <xsl:if test="$files">
      <xsl:variable name="modules" select="/page/boxes/box[generate-id(.) = generate-id(key('box-modules', @module)[1])]/@module"/>
      <xsl:for-each select="$modules">
        <xsl:sort select="." />
        <xsl:variable name="currentModule" select="."/>
        <xsl:for-each select="$files[ancestor::*[@module = $currentModule]]">
          <xsl:if test="@href and @href != ''">
            <file><xsl:value-of select="@href"/></file>
          </xsl:if>
        </xsl:for-each>
      </xsl:for-each>
    </xsl:if>
  </xsl:variable>
  <xsl:variable name="sorted">
    <xsl:for-each select="exsl:node-set($xml)/*">
      <xsl:sort select="."/>
      <xsl:copy-of select="."/>
    </xsl:for-each>
  </xsl:variable>
  <xsl:variable name="result">
    <xsl:variable name="nodes" select="exsl:node-set($sorted)/*"/>
    <xsl:for-each select="$nodes">
      <xsl:variable name="currentPosition" select="position()"/>
      <xsl:variable name="previousPosition" select="position() -1"/>
      <xsl:choose>
        <xsl:when test="string(.) != string($nodes[$previousPosition])">
          <xsl:copy-of select="."/>
        </xsl:when>
      </xsl:choose>
    </xsl:for-each>
  </xsl:variable>
  <func:result select="$result"/>
</func:function>

<xsl:template name="link-script">
  <xsl:param name="file"></xsl:param>
  <xsl:param name="files">
    <file><xsl:value-of select="$file"/></file>
  </xsl:param>
  <xsl:param name="type">text/javascript</xsl:param>
  <xsl:param name="merge" select="not($PAPAYA_DBG_DEVMODE)"/>
  <xsl:param name="condition" select="false()"/>
  <xsl:call-template name="link-resource">
    <xsl:with-param name="files" select="$files"/>
    <xsl:with-param name="type" select="$type"/>
    <xsl:with-param name="merge" select="$merge"/>
    <xsl:with-param name="condition" select="$condition" />
  </xsl:call-template>
</xsl:template>

<xsl:template name="link-style">
  <xsl:param name="file"></xsl:param>
  <xsl:param name="files">
    <file><xsl:value-of select="$file"/></file>
  </xsl:param>
  <xsl:param name="media">screen, projection</xsl:param>
  <xsl:param name="merge" select="not($PAPAYA_DBG_DEVMODE)"/>
  <xsl:param name="condition" select="false()"/>
  <xsl:call-template name="link-resource">
    <xsl:with-param name="files" select="$files"/>
    <xsl:with-param name="type">text/css</xsl:with-param>
    <xsl:with-param name="media" select="$media"/>
    <xsl:with-param name="merge" select="$merge"/>
    <xsl:with-param name="condition" select="$condition" />
  </xsl:call-template>
</xsl:template>

<!--  embed resources, css and javascript -->

<xsl:template name="link-resource">
  <xsl:param name="files"/>
  <xsl:param name="type">text/css</xsl:param>
  <xsl:param name="media">screen, projection</xsl:param>
  <xsl:param name="condition" select="false()"/>
  <xsl:param name="merge" select="false()"/>
  
  <xsl:choose>
    <xsl:when test="$condition and $condition != ''">
      <xsl:call-template name="conditional-comment">
        <xsl:with-param name="condition" select="$condition" />
        <xsl:with-param name="content">
          <xsl:call-template name="link-resource">
            <xsl:with-param name="files" select="$files"/>
            <xsl:with-param name="type" select="$type"/>
            <xsl:with-param name="media" select="$media"/>
            <xsl:with-param name="merge" select="$merge"/>
          </xsl:call-template>
        </xsl:with-param>
      </xsl:call-template>
    </xsl:when>
    <xsl:when test="exsl:object-type($files) = 'RTF' and count(exsl:node-set($files)/*) &gt; 0">
      <xsl:call-template name="link-resource">
        <xsl:with-param name="files" select="exsl:node-set($files)/*"/>
        <xsl:with-param name="type" select="$type"/>
        <xsl:with-param name="media" select="$media"/>
        <xsl:with-param name="merge" select="$merge"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:when test="exsl:object-type($files) = 'RTF'">
      <xsl:call-template name="link-resource">
        <xsl:with-param name="files" select="exsl:node-set($files)"/>
        <xsl:with-param name="type" select="$type"/>
        <xsl:with-param name="media" select="$media"/>
        <xsl:with-param name="merge" select="$merge"/>
      </xsl:call-template>
    </xsl:when>
    <xsl:when test="exsl:object-type($files) = 'node-set'">
      <xsl:if test="count($files) &gt; 0">
        <xsl:variable name="wrapper">
          <xsl:choose>
            <xsl:when test="$type = 'text/javascript'">js.php</xsl:when>
            <xsl:otherwise>css.php</xsl:otherwise>
          </xsl:choose>
        </xsl:variable>
        <xsl:choose>
          <xsl:when test="$merge">
            <xsl:variable name="href">
              <xsl:value-of select="$PAGE_THEME_PATH"/>
              <xsl:value-of select="$wrapper"/>
              <xsl:text>?files=</xsl:text>
              <xsl:for-each select="$files">
                <xsl:if test="position() &gt; 1">
                  <xsl:text>,</xsl:text>
                </xsl:if>
                <xsl:value-of select="."/>
              </xsl:for-each>
              <xsl:text>&amp;rev=</xsl:text>
              <xsl:value-of select="str:encode-uri($PAGE_WEBSITE_REVISION, true())"/>
              <xsl:if test="number($PAGE_THEME_SET) &gt; 0">
                <xsl:text>&amp;set=</xsl:text>
                <xsl:value-of select="str:encode-uri($PAGE_THEME_SET, true())"/>
              </xsl:if>
            </xsl:variable>
            <xsl:choose>
              <xsl:when test="$type = 'text/css'">
                <link rel="stylesheet" type="text/css" href="{$href}" media="{$media}"/>
              </xsl:when>
              <xsl:otherwise>
                <script type="{$type}" src="{$href}"><xsl:text> </xsl:text></script>
              </xsl:otherwise>
            </xsl:choose>
          </xsl:when>
          <xsl:otherwise>
            <xsl:for-each select="$files">
              <xsl:variable name="href">
                <xsl:value-of select="$PAGE_THEME_PATH"/>
                <xsl:value-of select="$wrapper"/>
                <xsl:text>?files=</xsl:text>
                <xsl:value-of select="."/>
                <xsl:text>&amp;rev=</xsl:text>
                <xsl:value-of select="str:encode-uri($PAGE_WEBSITE_REVISION, true())"/>
                <xsl:if test="number($PAGE_THEME_SET) &gt; 0">
                  <xsl:text>&amp;set=</xsl:text>
                  <xsl:value-of select="str:encode-uri($PAGE_THEME_SET, true())"/>
                </xsl:if>
              </xsl:variable>
              <xsl:choose>
                <xsl:when test="$type = 'text/css'">
                  <link rel="stylesheet" type="text/css" href="{$href}" media="{$media}"/>
                </xsl:when>
                <xsl:otherwise>
                  <script type="{$type}" src="{$href}"><xsl:text> </xsl:text></script>
                </xsl:otherwise>
              </xsl:choose>
            </xsl:for-each>
          </xsl:otherwise>
        </xsl:choose>
      </xsl:if>
    </xsl:when>
  </xsl:choose>
</xsl:template>

</xsl:stylesheet>