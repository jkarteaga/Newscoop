<?php  
require_once($_SERVER['DOCUMENT_ROOT']. "/$ADMIN_DIR/pub/issues/sections/articles/article_common.php");

list($access, $User) = check_basic_access($_REQUEST);
if (!$access) {
	header("Location: /$ADMIN/logout.php");
	exit;
}

$Pub = Input::get('Pub', 'int', 0);
$Issue = Input::get('Issue', 'int', 0);
$Section = Input::get('Section', 'int', 0);
$Language = Input::get('Language', 'int', 0);
$sLanguage = Input::get('sLanguage', 'int', 0);
$Article = Input::get('Article', 'int', 0);
$LockOk = Input::get('LockOk', 'string', 0, true);

if (!Input::isValid()) {
	header("Location: /$ADMIN/logout.php");
	exit;	
}

$errorStr = "";

// Fetch article
$articleObj =& new Article($Pub, $Issue, $Section, $sLanguage, $Article);
if (!$articleObj->exists()) {
	$errorStr = 'No such article.';
}
$articleType =& $articleObj->getArticleTypeObject();
$lockUserObj =& new User($articleObj->getLockedByUser());
$languageObj =& new Language($Language);
$sLanguageObj =& new Language($sLanguage);
$issueObj =& new Issue($Pub, $Language, $Issue);
$articleTemplate =& new Template($issueObj->getArticleTemplateId());

// If the user has the ability to change the article OR
// the user created the article and it hasnt been published.
$hasAccess = false;
if ($User->hasPermission('ChangeArticle') || (($articleObj->getUserId() == $User->getId()) && ($articleObj->getPublished() == 'N'))) {
	$hasAccess = true;
	$edit_ok = 0;
	// If the article is not locked by a user or its been locked by the current user.
	if (($articleObj->getLockedByUser() == 0) 
		|| ($articleObj->getLockedByUser() == $User->getId())) {
		// Lock the article
		$articleObj->lock($User->getId());
	    $edit_ok = 1;
	} 
	// If the user who locked the article doesnt exist anymore, unlock the article.
	if (!$lockUserObj->exists()) {
		$articleObj->unlock();
		$edit_ok = 1;
	}
}

if ($User->hasPermission('AddArticle')) { 
	// Added by sebastian.
	if (function_exists ("incModFile")) {
		incModFile ();
	}
}

// Check if everything needed for Article Import is available.
$zipLibAvailable = function_exists("zip_open");
$xsltLibAvailable = function_exists("xslt_create");
@include("XML/Parser.php");
$xmlLibAvailable = class_exists("XML_Parser");
$xmlLibAvailable |= function_exists("xml_parser_create");
// Verify this article type has the body & intro fields.
$introSupport = false;
$bodySupport = false;
$dbColumns = $articleType->getUserDefinedColumns();
foreach ($dbColumns as $dbColumn) {
	if ($dbColumn->getName() == "Fintro") {
		$introSupport = true;
	}
	if ($dbColumn->getName() == "Fbody") {
		$bodySupport = true;
	}
}

// Begin Display of page
ArticleTop($articleObj, $Language, "Edit article details");
HtmlArea_Campsite($dbColumns);

if ($errorStr != "") {
	CampsiteInterface::DisplayError($errorStr);
	return;
}

if (!$hasAccess) {
	?>
	<P>
	<CENTER><TABLE BORDER="0" CELLSPACING="0" CELLPADDING="8" BGCOLOR="#C0D0FF" ALIGN="CENTER">
		<TR>
			<TD COLSPAN="2">
				<B> <font color="red"><?php  putGS("Access denied"); ?> </font></B>
				<HR NOSHADE SIZE="1" COLOR="BLACK">
			</TD>
		</TR>
		<TR>
			<TD COLSPAN="2"><BLOCKQUOTE><font color=red><li><?php  putGS("You do not have the right to change this article.  You may only edit your own articles and once submitted an article can only changed by authorized users." ); ?></li></font></BLOCKQUOTE></TD>
		</TR>
		<TR>
			<TD COLSPAN="2">
			<DIV ALIGN="CENTER">
			<A HREF="/<?php echo $ADMIN; ?>/pub/issues/sections/articles/?Pub=<?php  p($Pub); ?>&Issue=<?php  p($Issue); ?>&Language=<?php  p($Language); ?>&Section=<?php  p($Section); ?>"><IMG SRC="/<?php echo $ADMIN; ?>/img/button/ok.gif" BORDER="0" ALT="OK"></A>
			</DIV>
			</TD>
		</TR>
	</TABLE></CENTER>
	</FORM>
	<P>
	<?php	
}

// If the article is locked.
if ($hasAccess && !$edit_ok) {
	?><P>
	<CENTER>
	<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="8" BGCOLOR="#C0D0FF" ALIGN="CENTER">
	<TR>
		<TD COLSPAN="2">
			<B><?php  putGS("Article is locked"); ?> </B>
			<HR NOSHADE SIZE="1" COLOR="BLACK">
		</TD>
	</TR>
	<TR>
		<TD COLSPAN="2"><BLOCKQUOTE><LI><?php  putGS('This article has been locked by $1 ($2) at','<B>'.htmlspecialchars($lockUserObj->getName()),htmlspecialchars($lockUserObj->getUserName()).'</B>' ); ?>
		<B><?php print htmlspecialchars($articleObj->getLockTime()); ?></B></LI>
		<LI><?php putGS('Now is $1','<B>'.date("Y-m-d G:i:s").'</B>'); ?></LI>
		<LI><?php putGS('Are you sure you want to unlock it?'); ?></LI>
		</BLOCKQUOTE></TD>
	</TR>
	<TR>
		<TD COLSPAN="2">
		<DIV ALIGN="CENTER">
		<INPUT TYPE="button" NAME="Yes" VALUE="<?php  putGS('Yes'); ?>" class="button" ONCLICK="location.href='<?php echo CampsiteInterface::ArticleUrl($articleObj, $sLanguage, "do_unlock.php"); ?>'">
		<INPUT TYPE="button" NAME="No" VALUE="<?php  putGS('No'); ?>" class="button" ONCLICK="location.href='/<?php echo $ADMIN; ?>/pub/issues/sections/articles/?Pub=<?php  p($Pub); ?>&Issue=<?php  p($Issue); ?>&Language=<?php p($Language); ?>&Section=<?php  p($Section); ?>'">
		</DIV>
		</TD>
	</TR>
	</TABLE></CENTER>
	<P>
	<?php  
}

if ($edit_ok) { ?>
<P>
<TABLE BORDER="0" CELLSPACING="1" CELLPADDING="0" WIDTH="100%">
<TR>
	<TD>
		<TABLE BORDER="0" CELLSPACING="1" CELLPADDING="0">
		<TR>
		<?php 
		if ($articleObj->getPublished() == "Y") { 
			if ($User->hasPermission('Publish')) { 
				?>
				<TD>
					<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="1">
					<TR>
						<TD><?php echo CampsiteInterface::ArticleLink($articleObj, $languageObj->getLanguageId(), "status.php", $REQUEST_URI); ?><IMG SRC="/<?php echo $ADMIN; ?>/img/tol.gif" BORDER="0"></A></TD>
						<TD><?php echo CampsiteInterface::ArticleLink($articleObj, $languageObj->getLanguageId(), "status.php", $REQUEST_URI); ?><B><?php  putGS("Unpublish"); ?></B></A></TD>
					</TR>
					</TABLE>
				</TD>
				<?php  
			} 
		} 
		elseif ($articleObj->getPublished() == "S") { 
			if ($User->hasPermission('Publish')) { 
				?>
				<TD>
					<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="1">
					<TR>
						<TD><?php echo CampsiteInterface::ArticleLink($articleObj, $languageObj->getLanguageId(), "status.php", $REQUEST_URI); ?><IMG SRC="/<?php echo $ADMIN; ?>/img/tol.gif" BORDER="0"></A></TD>
						<TD><?php echo CampsiteInterface::ArticleLink($articleObj, $languageObj->getLanguageId(), "status.php", $REQUEST_URI); ?><B><?php  putGS("Publish"); ?></B></A></TD>
					</TR>
					</TABLE>
				</TD>
				<?php
			} 
		} 
		elseif ($articleObj->getPublished() == "N") { 
			if ($articleObj->getUserId() == $User->getId()) {
				?>
				<TD>
					<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="1">
					<TR>
						<TD><?php echo CampsiteInterface::ArticleLink($articleObj, $languageObj->getLanguageId(), "status.php", $REQUEST_URI); ?>
						<IMG SRC="/<?php echo $ADMIN; ?>/img/tol.gif" BORDER="0"></A></TD>
						<TD><?php echo CampsiteInterface::ArticleLink($articleObj, $languageObj->getLanguageId(), "status.php", $REQUEST_URI); ?><B><?php  putGS("Submit"); ?></B></A></TD>
					</TR>
					</TABLE>
				</TD>
				<?php  
			}
		} 
		
		if ($User->hasPermission('AddImage') || $User->hasPermission('DeleteImage') || $User->hasPermission('ChangeArticle') || $User->hasPermission('ChangeImage')) {
		?>
			<TD>
				<!-- Images Link -->
				<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="1">
				<TR>
					<TD><?php echo CampsiteInterface::ArticleLink($articleObj, $Language, "images/"); ?><IMG SRC="/<?php echo $ADMIN; ?>/img/tol.gif" BORDER="0"></A></TD>
					<TD><?php echo CampsiteInterface::ArticleLink($articleObj, $Language, "images/"); ?><B><?php  putGS("Images"); ?></B></A></TD>
				</TR>
				</TABLE>
			</TD>
		<?php
		}
		?>
			<TD>
				<!-- Topics Link -->
				<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="1">
				<TR>
					<TD><?php echo CampsiteInterface::ArticleLink($articleObj, $languageObj->getLanguageId(), "topics/"); ?><IMG SRC="/<?php echo $ADMIN; ?>/img/tol.gif" BORDER="0"></A></TD>
					<TD><?php echo CampsiteInterface::ArticleLink($articleObj, $languageObj->getLanguageId(), "topics/"); ?><B><?php  putGS("Topics"); ?></B></A></TD>
				</TR>
				</TABLE>
			</TD>
			
			<TD>
				<!-- Unlock Link -->
				<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="1">
				<TR>
					<TD><?php echo CampsiteInterface::ArticleLink($articleObj, $languageObj->getLanguageId(), "do_unlock.php"); ?><IMG SRC="/<?php echo $ADMIN; ?>/img/tol.gif" BORDER="0"></A></TD>
					<TD><?php echo CampsiteInterface::ArticleLink($articleObj, $languageObj->getLanguageId(), "do_unlock.php"); ?><B><?php  putGS("Unlock"); ?></B></A></TD>
				</TR>
				</TABLE>
			</TD>
		</TR>		
		
		<TR>
			<TD>
				<!-- Preview Link -->
				<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="1">
				<TR>	
					<TD><A HREF="" ONCLICK="window.open('/<?php echo $ADMIN; ?>/pub/issues/sections/articles/preview.php?Pub=<?php  p($Pub); ?>&Issue=<?php  p($Issue); ?>&Section=<?php  p($Section); ?>&Article=<?php  p($Article); ?>&Language=<?php  p($Language); ?>&sLanguage=<?php  p($sLanguage); ?>', 'fpreview', 'resizable=yes, menubar=no, toolbar=no, width=680, height=560'); return false"><IMG SRC="/<?php echo $ADMIN; ?>/img/tol.gif" BORDER="0"></A></TD>
					<TD><A HREF="" ONCLICK="window.open('/<?php echo $ADMIN; ?>/pub/issues/sections/articles/preview.php?Pub=<?php  p($Pub); ?>&Issue=<?php  p($Issue); ?>&Section=<?php  p($Section); ?>&Article=<?php  p($Article); ?>&Language=<?php  p($Language); ?>&sLanguage=<?php  p($sLanguage); ?>', 'fpreview', 'resizable=yes, menubar=yes, toolbar=yes, width=680, height=560'); return false"><B><?php  putGS("Preview"); ?></B></A></TD>
				</TR>
				</TABLE>
			</TD>

			<?php  
			if ($User->hasPermission('AddArticle')) { 
				?>
				<TD>
					<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="1">
					<TR>
						<TD><?php echo CampsiteInterface::ArticleLink($articleObj, $languageObj->getLanguageId(), "translate.php", $REQUEST_URI); ?><IMG SRC="/<?php echo $ADMIN; ?>/img/tol.gif" BORDER="0"></A></TD>
						<TD><?php echo CampsiteInterface::ArticleLink($articleObj, $languageObj->getLanguageId(), "translate.php", $REQUEST_URI); ?><B><?php  putGS("Translate"); ?></B></A></TD>
					</TR>
					</TABLE>
				</TD>
				<?php  
			} 

			if ($User->hasPermission('DeleteArticle')) { 
				?>
				<TD>
					<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="1">
					<TR>
						<TD><a href="/<?php echo $ADMIN; ?>/pub/issues/sections/articles/do_del.php?Pub=<?php p($Pub); ?>&Issue=<?php p($Issue); ?>&Section=<?php p($Section); ?>&Article=<?php p($Article); ?>&Language=<?php p($Language); ?>&sLanguage=<?php p($sLanguage); ?>" onclick="return confirm('<?php htmlspecialchars(putGS('Are you sure you want to delete the article $1 ($2)?',$articleObj->getTitle(),$sLanguageObj->getName())); ?>');"><IMG SRC="/<?php echo $ADMIN; ?>/img/tol.gif" BORDER="0"></A></TD>
						<TD><a href="/<?php echo $ADMIN; ?>/pub/issues/sections/articles/do_del.php?Pub=<?php p($Pub); ?>&Issue=<?php p($Issue); ?>&Section=<?php p($Section); ?>&Article=<?php p($Article); ?>&Language=<?php p($Language); ?>&sLanguage=<?php p($sLanguage); ?>" onclick="return confirm('<?php htmlspecialchars(putGS('Are you sure you want to delete the article $1 ($2)?',$articleObj->getTitle(),$sLanguageObj->getName())); ?>');"><B><?php  putGS("Delete"); ?></B></A></TD>
					</TR>
					</TABLE>
				</TD>
				<?php  
			} 

			if ($User->hasPermission('AddArticle')) { 
				?>
				<TD>
					<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="1">
					<TR>
						<TD><A HREF="<?php echo CampsiteInterface::ArticleUrl($articleObj, $languageObj->getLanguageId(), "duplicate.php"); ?>&Back=<?php p(urlencode($REQUEST_URI)); ?>"><IMG SRC="/<?php echo $ADMIN; ?>/img/tol.gif" BORDER="0"></A></TD>
						<TD><A HREF="<?php echo CampsiteInterface::ArticleUrl($articleObj, $languageObj->getLanguageId(), "duplicate.php"); ?>&Back=<?php p(urlencode($REQUEST_URI)); ?>"><B><?php  putGS("Duplicate"); ?></B></A></TD>
					</TR>
					</TABLE>
				</TD>
				<?php  
			} 
			?>
		</TR>
		</TABLE>
	</TD>
	
	<TD ALIGN="RIGHT">
		<FORM METHOD="GET" ACTION="edit.php" NAME="">
		<INPUT TYPE="HIDDEN" NAME="Pub" VALUE="<?php  p($Pub); ?>">
		<INPUT TYPE="HIDDEN" NAME="Issue" VALUE="<?php  p($Issue); ?>">
		<INPUT TYPE="HIDDEN" NAME="Section" VALUE="<?php  p($Section); ?>">
		<INPUT TYPE="HIDDEN" NAME="Article" VALUE="<?php  p($Article); ?>">
		<INPUT TYPE="HIDDEN" NAME="Language" VALUE="<?php  p($Language); ?>">
		<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="3" class="table_input">
		<TR>
			<TD><?php  putGS('Language'); ?>:</TD>
			<TD>
				<SELECT NAME="sLanguage" class="input_select">
				<?php 
					$articleLanguages = $articleObj->getLanguages();
					foreach ($articleLanguages as $articleLanguage) {
					    pcomboVar($articleLanguage->getLanguageId(), $sLanguage, htmlspecialchars($articleLanguage->getName()));
					}
				?></SELECT>
			</TD>
			<TD>
				<INPUT TYPE="submit" NAME="Search" VALUE="<?php  putGS('Search'); ?>" class="button">
			</TD>
		</TR>
		</TABLE>
		</FORM>
	</TD>
</TR>
</TABLE>

<FORM NAME="dialog" METHOD="POST" ACTION="do_edit.php">
<INPUT TYPE="HIDDEN" NAME="Pub" VALUE="<?php  p($Pub); ?>">
<INPUT TYPE="HIDDEN" NAME="Issue" VALUE="<?php  p($Issue); ?>">
<INPUT TYPE="HIDDEN" NAME="Section" VALUE="<?php  p($Section); ?>">
<INPUT TYPE="HIDDEN" NAME="Article" VALUE="<?php  p($Article); ?>">
<INPUT TYPE="HIDDEN" NAME="Language" VALUE="<?php  p($Language); ?>">
<INPUT TYPE="HIDDEN" NAME="sLanguage" VALUE="<?php  p($sLanguage); ?>">

<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="6" align="center" class="table_input">
<TR>
	<TD COLSPAN="2">
		<table cellpadding="0" cellspacing="0" width="100%">
		<tr>
			<td align="left">
				<B><?php putGS("Edit article details"); ?></B>
			</td>
			<td align="right">
				<?php 
				if (false) {
				//if ($zipLibAvailable && $xsltLibAvailable && $xmlLibAvailable 
				//		&& $introSupport && $bodySupport) {
					// Article Import Link
					?>
					<b><a href="/<?php echo $ADMIN; ?>/article_import/index.php?Pub=<?p($Pub);?>&Issue=<?p($Issue);?>&Section=<?p($Section);?>&Article=<?p($Article)?>&Language=<?p($Language);?>&sLanguage=<?p($sLanguage);?>">Import Article</a></b>
					<?php
				}
				?>
			</td>
		</tr>
		</table>
		<HR NOSHADE SIZE="1" COLOR="BLACK"> 
	</TD>
</TR>
<TR>
	<TD ALIGN="RIGHT" ><?php  putGS("Name"); ?>:</TD>
	<TD>
		<INPUT TYPE="TEXT" NAME="cName" SIZE="64" MAXLENGTH="140" VALUE="<?php  print htmlspecialchars($articleObj->getTitle()); ?>" class="input_text">
	</TD>
</TR>
<TR>
	<TD ALIGN="RIGHT" ><?php  putGS("Type"); ?>:</TD>
	<TD>
		<?php print htmlspecialchars($articleObj->getType()); ?>
	</TD>
</TR>
<TR>
	<TD ALIGN="RIGHT" ><?php  putGS("Uploaded"); ?>:</TD>
	<TD>
		<?php print htmlspecialchars($articleObj->getUploadDate()); ?> <?php  putGS('(yyyy-mm-dd)'); ?>
	</TD>
</TR>
<TR>
	<TD>&nbsp;</TD><TD>
	<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="1" WIDTH="100%">
	<TR>
		<TD ALIGN="RIGHT" ><INPUT TYPE="CHECKBOX" NAME="cOnFrontPage"<?php  if ($articleObj->onFrontPage()) { ?> class="input_checkbox" CHECKED<?php  } ?>></TD>
		<TD>
		<?php  putGS('Show article on front page'); ?>
		</TD>
	</TR>
	<TR>
		<TD ALIGN="RIGHT" ><INPUT TYPE="CHECKBOX" NAME="cOnSection"<?php  if ($articleObj->onSection()) { ?> class="input_checkbox" CHECKED<?php  } ?>></TD>
		<TD>
		<?php  putGS('Show article on section page'); ?>
		</TD>
	</TR>
	<TR>
		<TD ALIGN="RIGHT" ><INPUT TYPE="CHECKBOX" NAME="cPublic"<?php  if ($articleObj->isPublic()) { ?> class="input_checkbox" CHECKED<?php  } ?>></TD>
		<TD>
		<?php putGS('Allow users without subscriptions to view the article'); ?>
		</TD>
	</TR>
		</TABLE>
	</TD>
	</TR>
	<TR>
		<TD ALIGN="RIGHT" ><?php  putGS("Keywords"); ?>:</TD>
		<TD>
			<INPUT TYPE="TEXT" NAME="cKeywords" VALUE="<?php print htmlspecialchars($articleObj->getKeywords()); ?>" class="input_text" SIZE="64" MAXLENGTH="255">
		</TD>
	</TR>

	<?php 
	// Display the article type fields.
	foreach ($dbColumns as $dbColumn) {
		if (stristr($dbColumn->getType(), "char")) { 
			// Single line text fields
			?>
			<TR>
				<TD ALIGN="RIGHT" ><?php echo htmlspecialchars($dbColumn->getPrintName()); ?>:</TD>
				<TD>
		        <INPUT NAME="<?php echo htmlspecialchars($dbColumn->getName()); ?>" 
					   TYPE="TEXT" 
					   VALUE="<?php print $articleType->getProperty($dbColumn->getName()) ?>" 
					   class="input_text"
					   SIZE="64" 
					   MAXLENGTH="100">
				</TD>
			</TR>
			<?php  
		} elseif (stristr($dbColumn->getType(), "date")) { 
			// Date fields
			if ($articleType->getProperty($dbColumn->getName()) == "0000-00-00") {
				$articleType->setProperty($dbColumn->getName(), "CURDATE()", true, true);
			}
			?>		
			<TR>
				<TD ALIGN="RIGHT" ><?php echo htmlspecialchars($dbColumn->getPrintName()); ?>:</TD>
				<TD>
				<INPUT NAME="<?php echo htmlspecialchars($dbColumn->getName()); ?>" 
					   TYPE="TEXT" 
					   VALUE="<?php echo htmlspecialchars($articleType->getProperty($dbColumn->getName())); ?>" 
					   class="input_text"
					   SIZE="11" 
					   MAXLENGTH="10"> 
				<?php putGS('YYYY-MM-DD'); ?>
				</TD>
			</TR>
			<?php
		} elseif (stristr($dbColumn->getType(), "blob")) {
			// Multiline text fields
			// Transform Campsite-specific tags into editor-friendly tags.
			$text = $articleType->getProperty($dbColumn->getName());
			
			$text = preg_replace("/<!\*\*\s*Title\s*>/i", "<span class=\"campsite_subhead\">", $text);
			$text = preg_replace("/<!\*\*\s*EndTitle\s*>/i", "</span>", $text);
			$text = preg_replace("/<!\*\*\s*Link\s*Internal\s*[\w&]*\s*>/i", "<a href=\"campsite_internal_link?\7\">", $text);
			$text = preg_replace("/<!\*\*\s*EndLink\s*>/i", "</a>", $text);
			?>
			<TR>
			<TD ALIGN="RIGHT" VALIGN="TOP"><BR><?php echo htmlspecialchars($dbColumn->getPrintName()); ?>:<BR> 
			</TD>
			<TD>
				<HR NOSHADE SIZE="1" COLOR="BLACK">
				<table width=100% border=2>
				<tr bgcolor=LightBlue>
					<td><textarea name="<?php print $dbColumn->getName() ?>" 
								  id="<?php print $dbColumn->getName() ?>" 
								  rows="20" cols="80" ><?php print $text; ?></textarea>
					</td>
				</tr>
				</table>
			<BR><P>
			</TD>
			</TR>
			<?php  
		}
	} // foreach ($dbColumns as $dbColumn)  
	?>
	<TR>
		<TD COLSPAN="2">
		<DIV ALIGN="CENTER">
		<INPUT TYPE="submit" NAME="Save" VALUE="<?php  putGS('Save changes'); ?>" class="button">
		<INPUT TYPE="button" NAME="Cancel" VALUE="<?php  putGS('Cancel'); ?>" class="button" ONCLICK="location.href='/<?php echo $ADMIN; ?>/pub/issues/sections/articles/?Pub=<?php  p($Pub); ?>&Issue=<?php  p($Issue); ?>&Section=<?php  p($Section); ?>&Language=<?php  p($Language); ?>'">
		</DIV>
		</TD>
	</TR>
</TABLE>
</FORM>
<?php  
} // if ($edit_ok)
CampsiteInterface::CopyrightNotice();
?>