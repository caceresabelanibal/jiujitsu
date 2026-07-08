<?php
$c = row('SELECT * FROM certificates WHERE id = ?', [(int)$params[0]]);
if (!$c) { http_response_code(404); exit('Not found'); }
require_tournament_owner((int)$c['tournament_id']);
if (!file_exists($c['pdf_path'])) { http_response_code(404); exit('PDF not found'); }
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($c['pdf_path']) . '"');
readfile($c['pdf_path']);
