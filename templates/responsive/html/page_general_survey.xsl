<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

  <xsl:import href="page_main.xsl" />

  <xsl:template name="content-area">
    <xsl:param name="content" select="/page/content/topic" />
    <xsl:if test="$content/subject">
      <div class="dentistSurveySubject">
        <xsl:value-of select="$content/subject/@caption" />
        <xsl:text>: </xsl:text>
        <strong><xsl:value-of select="$content/subject/text()" /></strong>
      </div>
    </xsl:if>
    <xsl:if test="$content/message">
      <div class="message {$content/message/@type}">
        <xsl:value-of select="$content/message/text()" />
      </div>
    </xsl:if>
    <xsl:choose>
      <xsl:when test="$content/questiongroup">
        <xsl:call-template name="displayQuestionGroup">
          <xsl:with-param name="content" select="$content" />
          <xsl:with-param name="questionGroup" select="$content/questiongroup" />
        </xsl:call-template>
      </xsl:when>
      <xsl:when test="$content/subject-select">
        <xsl:call-template name="displaySubjectSelect">
          <xsl:with-param name="dialog" select="$content/subject-select/dialog" />
          <xsl:with-param name="subjects" select="$content/subject-select/subjects" />
        </xsl:call-template>
      </xsl:when>
      <xsl:when test="$content/repeat">
        <p>
          <a href="{$content/repeat/@href}"><xsl:value-of select="$content/repeat/text()" /></a>
        </p>
      </xsl:when>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="displayQuestionGroup">
    <xsl:param name="content" />
    <xsl:param name="questionGroup" />
    <xsl:param name="dialog" select="$questionGroup/dialog" />
    <h2><xsl:value-of select="$questionGroup/title/text()" /></h2>
    <xsl:copy-of select="$questionGroup/text/*|$questionGroup/text/text()" />
    <form action="{$dialog/@action}" method="{$dialog/@method}">
      <xsl:copy-of select="$dialog/input[@type = 'hidden']" />
      <table class="surveyQuestionGroup">
      <xsl:for-each select="$questionGroup/questions/question">
        <xsl:variable name="questionId"><xsl:value-of select="@id" /></xsl:variable>
        <tr>
          <th colspan="{count(./answer)}">
            <xsl:if test="count($content/unanswered/question[@id = $questionId]) &gt; 0">
              <xsl:attribute name="class">error</xsl:attribute>
            </xsl:if>
            <p><strong><xsl:value-of select="$dialog/lines/line[@fid = $questionId]/@caption" /></strong></p>
            <xsl:copy-of select="description/*|description/text()" />
          </th>
        </tr>
        <tr>
          <xsl:for-each select="answer">
            <xsl:variable name="answerId"><xsl:value-of select="@id" /></xsl:variable>
            <xsl:variable name="answer" select="$dialog/lines/line[@fid = $questionId]/input[@value = $answerId]" />
            <td>
              <table class="tableBorderless">
                <tr>
                  <td>
                    <input type="{$answer/@type}" name="{$answer/@name}" value="{$answer/@value}" id="answer_{$answerId}">
                      <xsl:if test="$answer/@checked = 'checked'">
                        <xsl:attribute name="checked">checked</xsl:attribute>
                      </xsl:if>
                    </input>
                  </td>
                  <td>
                    <label for="answer_{$answerId}"><xsl:value-of select="$answer/text()" /></label>
                  </td>
                </tr>
              </table>
            </td>
          </xsl:for-each>
        </tr>
      </xsl:for-each>
      </table>
      <div class="formElement topMargin">
				<span class="submitButton">
					<span class="submitButtonSide"><xsl:text> </xsl:text></span>
					<span class="submitButtonContent">
						<input type="submit" value="{$dialog/dlgbutton/@value}" />
					</span>
				</span>
      </div>
    </form>
  </xsl:template>

  <xsl:template name="displaySubjectSelect">
    <xsl:param name="dialog" />
    <xsl:param name="subjects" />
    <h2><xsl:value-of select="$dialog/@title" /></h2>
    <form action="{$dialog/@action}" method="{$dialog/@method}">
      <xsl:copy-of select="$dialog/input[@type = 'hidden']" />
      <label for="subject_id"><xsl:value-of select="$dialog/lines/line[@fid = 'subject_id']/@caption" /></label>
      <input type="text" id="subjectSuggest" size="10" />
      <select name="{$dialog/lines/line[@fid = 'subject_id']/select/@name}" id="subjectSelect">
        <xsl:copy-of select="$dialog/lines/line[@fid = 'subject_id']/select/option" />
      </select>
      <span class="submitButton">
        <span class="submitButtonSide"><xsl:text> </xsl:text></span>
        <span class="submitButtonContent">
          <input type="submit" value="{$dialog/dlgbutton/@value}" />
        </span>
      </span>
    </form>
    <script type="text/javascript">
      subjects = [];
      <xsl:for-each select="$subjects/subject">
        subjects.push([<xsl:value-of select="@id" />, '<xsl:value-of select="text()" />']);
      </xsl:for-each>
    </script>
  </xsl:template>

</xsl:stylesheet>
