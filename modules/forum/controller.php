<?php
/**
 * ClubCMS — Module Forum
 * Routes : /forum, /forum/categorie/{slug}, /forum/topic/{slug}, /forum/nouveau
 */

$action = $segments[1] ?? 'index';
$param  = $segments[2] ?? null;

// ── POST : Nouveau topic ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'nouveau') {
    Auth::require('member');
    if (!Auth::verifyCsrf()) Helpers::json(['error' => 'CSRF'], 403);

    $catId  = (int)($_POST['category_id'] ?? 0);
    $title  = Helpers::sanitize($_POST['title'] ?? '');
    $body   = trim($_POST['content'] ?? '');

    if (!$catId || strlen($title) < 3 || strlen($body) < 10) {
        $formError = 'Veuillez remplir tous les champs (titre min. 3 car., message min. 10 car.).';
    } else {
        $cat  = Database::one("SELECT * FROM cc_forum_categories WHERE id = ?", [$catId]);
        if (!$cat) { http_response_code(404); exit; }

        $slug    = Helpers::uniqueSlug($title, 'cc_forum_topics');
        $topicId = Database::insert(
            "INSERT INTO cc_forum_topics (category_id, user_id, title, slug, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())",
            [$catId, Auth::id(), $title, $slug]
        );
        Database::insert(
            "INSERT INTO cc_forum_posts (topic_id, user_id, content, is_first_post, created_at)
             VALUES (?, ?, ?, 1, NOW())",
            [$topicId, Auth::id(), $body]
        );

        Helpers::redirect('/forum/topic/' . $slug);
    }
}

// ── POST : Nouvelle réponse ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'topic') {
    Auth::require('member');
    if (!Auth::verifyCsrf()) Helpers::json(['error' => 'CSRF'], 403);

    $topicSlug = $param;
    $topic = Database::one("SELECT * FROM cc_forum_topics WHERE slug = ? AND locked = 0", [$topicSlug]);
    if (!$topic) { http_response_code(404); exit; }

    $body = trim($_POST['content'] ?? '');
    if (strlen($body) < 3) {
        $formError = 'Le message est trop court.';
    } else {
        Database::insert(
            "INSERT INTO cc_forum_posts (topic_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())",
            [$topic['id'], Auth::id(), $body]
        );
        Database::run(
            "UPDATE cc_forum_topics SET updated_at = NOW() WHERE id = ?",
            [$topic['id']]
        );

        // Notification email à l'auteur du topic (si différent)
        if ($topic['user_id'] !== Auth::id()) {
            $topicAuthor = Database::one("SELECT email, firstname FROM cc_users WHERE id = ?", [$topic['user_id']]);
            if ($topicAuthor) {
                Mailer::sendForumReply($topic, $topicAuthor['email'], $topicAuthor['firstname']);
            }
        }

        // Redirige vers la dernière page
        $nbPosts = Database::scalar("SELECT COUNT(*) FROM cc_forum_posts WHERE topic_id = ?", [$topic['id']]);
        $lastPage = max(1, ceil($nbPosts / 20));
        Helpers::redirect('/forum/topic/' . $topicSlug . '?page=' . $lastPage . '#bottom');
    }
}

$pageTitle = 'Forum — ' . Config::get('club_name');
ob_start();?>
<?php if ($action === 'index'): ?>
<!-- ═══════════════════════════════════════ LISTE DES CATÉGORIES -->
<div class="forum-hero">
  <div class="container">
    <?php $ph_t=Config::get('ph_forum_title',''); $ph_s=Config::get('ph_forum_subtitle',''); ?>
    <h1 class="forum-title"><?=$ph_t?Helpers::e($ph_t):'💬 Forum'?></h1>
    <?php if($ph_s): ?><p class="forum-subtitle"><?=Helpers::e($ph_s)?></p>
    <?php else: ?><p class="forum-subtitle">Échangez avec les membres du club</p><?php endif; ?>
  </div>
</div>

<div class="container forum-wrap">
  <?php if (Auth::check()): ?>
    <div class="forum-topbar">
      <a href="/forum/nouveau" class="btn btn-primary">✏️ Nouveau sujet</a>
    </div>
  <?php endif; ?>

  <?php
  $categories = Database::all("SELECT * FROM cc_forum_categories ORDER BY `order` ASC, id ASC");
  foreach ($categories as $cat):
    $nbTopics = Database::scalar("SELECT COUNT(*) FROM cc_forum_topics WHERE category_id = ?", [$cat['id']]);
    $nbPosts  = Database::scalar(
        "SELECT COUNT(*) FROM cc_forum_posts p
         JOIN cc_forum_topics t ON p.topic_id = t.id WHERE t.category_id = ?", [$cat['id']]
    );
    $lastPost = Database::one(
        "SELECT p.created_at, u.firstname, u.lastname, t.title, t.slug
         FROM cc_forum_posts p
         JOIN cc_forum_topics t ON p.topic_id = t.id
         JOIN cc_users u ON p.user_id = u.id
         WHERE t.category_id = ? ORDER BY p.created_at DESC LIMIT 1", [$cat['id']]
    );
  ?>
  <div class="forum-category card">
    <div class="fc-icon"><?= Helpers::e($cat['icon'] ?? '💬') ?></div>
    <div class="fc-info">
      <a href="/forum/categorie/<?= Helpers::e($cat['slug']) ?>" class="fc-name"><?= Helpers::e($cat['name']) ?></a>
      <?php if ($cat['description']): ?>
        <p class="fc-desc"><?= Helpers::e($cat['description']) ?></p>
      <?php endif; ?>
    </div>
    <div class="fc-stats">
      <div><strong><?= $nbTopics ?></strong><span>Sujets</span></div>
      <div><strong><?= $nbPosts ?></strong><span>Messages</span></div>
    </div>
    <div class="fc-last">
      <?php if ($lastPost): ?>
        <div class="fc-last-date"><?= Helpers::timeAgo($lastPost['created_at']) ?></div>
        <a href="/forum/topic/<?= $lastPost['slug'] ?>" class="fc-last-topic">
          <?= Helpers::e(Helpers::excerpt($lastPost['title'], 40)) ?>
        </a>
        <div class="fc-last-by">par <?= Helpers::e($lastPost['firstname'] . ' ' . $lastPost['lastname']) ?></div>
      <?php else: ?>
        <span class="fc-last-date">Aucun message</span>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <?php if (empty($categories)): ?>
    <div class="empty-state">
      <div class="empty-icon">💬</div>
      <p>Aucune catégorie créée pour le moment.</p>
      <?php if (Auth::isAdmin()): ?>
        <a href="/admin/forum" class="btn btn-primary">Créer une catégorie</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<?php elseif ($action === 'categorie' && $param): ?>
<!-- ═══════════════════════════════════════ LISTE DES TOPICS -->
<?php
$cat = Database::one("SELECT * FROM cc_forum_categories WHERE slug = ?", [$param]);
if (!$cat) { http_response_code(404); include CC_ROOT . '/templates/404.php'; exit; }

$total  = (int)Database::scalar("SELECT COUNT(*) FROM cc_forum_topics WHERE category_id = ?", [$cat['id']]);
$pager  = Helpers::paginate($total, 20);
$topics = Database::all(
    "SELECT t.*, u.firstname, u.lastname, u.avatar,
            (SELECT COUNT(*) FROM cc_forum_posts p WHERE p.topic_id = t.id) - 1 AS replies,
            (SELECT p2.created_at FROM cc_forum_posts p2 WHERE p2.topic_id = t.id ORDER BY p2.created_at DESC LIMIT 1) AS last_post_at,
            (SELECT u2.firstname FROM cc_forum_posts p3 JOIN cc_users u2 ON p3.user_id = u2.id WHERE p3.topic_id = t.id ORDER BY p3.created_at DESC LIMIT 1) AS last_poster
     FROM cc_forum_topics t
     JOIN cc_users u ON t.user_id = u.id
     WHERE t.category_id = ?
     ORDER BY t.pinned DESC, t.updated_at DESC
     LIMIT ? OFFSET ?",
    [$cat['id'], $pager['perPage'], $pager['offset']]
);
$pageTitle = Helpers::e($cat['name']) . ' — Forum — ' . Config::get('club_name');
?>
<div class="forum-hero">
  <div class="container">
    <nav class="breadcrumb">
      <a href="/forum">Forum</a> <span>›</span> <span><?= Helpers::e($cat['name']) ?></span>
    </nav>
    <h1 class="forum-title"><?= Helpers::e($cat['icon'] ?? '💬') ?> <?= Helpers::e($cat['name']) ?></h1>
    <?php if ($cat['description']): ?><p class="forum-subtitle"><?= Helpers::e($cat['description']) ?></p><?php endif; ?>
  </div>
</div>

<div class="container forum-wrap">
  <?php if (Auth::check()): ?>
    <div class="forum-topbar">
      <span style="color:var(--color-muted);font-size:.875rem"><?= $total ?> sujet<?= $total > 1 ? 's' : '' ?></span>
      <a href="/forum/nouveau?cat=<?= $cat['id'] ?>" class="btn btn-primary">✏️ Nouveau sujet</a>
    </div>
  <?php endif; ?>

  <div class="topic-list">
    <?php foreach ($topics as $t): ?>
    <div class="topic-item <?= $t['pinned'] ? 'pinned' : '' ?> <?= $t['locked'] ? 'locked' : '' ?>">
      <div class="topic-icon">
        <?= $t['pinned'] ? '📌' : ($t['locked'] ? '🔒' : '💬') ?>
      </div>
      <div class="topic-info">
        <a href="/forum/topic/<?= Helpers::e($t['slug']) ?>" class="topic-title">
          <?= Helpers::e($t['title']) ?>
        </a>
        <div class="topic-meta">
          Par <strong><?= Helpers::e($t['firstname'] . ' ' . $t['lastname']) ?></strong>
          · <?= Helpers::timeAgo($t['created_at']) ?>
        </div>
      </div>
      <div class="topic-stats">
        <div><span class="ts-val"><?= (int)$t['replies'] ?></span><span class="ts-lab">Réponses</span></div>
        <div><span class="ts-val"><?= $t['views'] ?></span><span class="ts-lab">Vues</span></div>
      </div>
      <div class="topic-last">
        <?php if ($t['last_post_at']): ?>
          <div class="tl-date"><?= Helpers::timeAgo($t['last_post_at']) ?></div>
          <div class="tl-by">par <?= Helpers::e($t['last_poster'] ?? '—') ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <?php if (empty($topics)): ?>
      <div class="empty-state"><div class="empty-icon">💬</div><p>Aucun sujet dans cette catégorie.</p></div>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($pager['pages'] > 1): ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $pager['pages']; $i++): ?>
      <a href="<?=u('/forum?page='.$i)?>" class="page-btn <?= $i === $pager['page'] ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</div>

<?php elseif ($action === 'topic' && $param): ?>
<!-- ═══════════════════════════════════════ TOPIC + POSTS -->
<?php
$topic = Database::one("SELECT t.*, u.firstname, u.lastname FROM cc_forum_topics t JOIN cc_users u ON t.user_id = u.id WHERE t.slug = ?", [$param]);
if (!$topic) { http_response_code(404); include CC_ROOT . '/templates/404.php'; exit; }

// Incrément vues
Database::run("UPDATE cc_forum_topics SET views = views + 1 WHERE id = ?", [$topic['id']]);

$cat    = Database::one("SELECT * FROM cc_forum_categories WHERE id = ?", [$topic['category_id']]);
$total  = (int)Database::scalar("SELECT COUNT(*) FROM cc_forum_posts WHERE topic_id = ?", [$topic['id']]);
$pager  = Helpers::paginate($total, 20);
$posts  = Database::all(
    "SELECT p.*, u.firstname, u.lastname, u.avatar, u.role, u.created_at AS member_since,
            (SELECT COUNT(*) FROM cc_forum_posts p2 WHERE p2.user_id = u.id) AS post_count
     FROM cc_forum_posts p JOIN cc_users u ON p.user_id = u.id
     WHERE p.topic_id = ?
     ORDER BY p.created_at ASC
     LIMIT ? OFFSET ?",
    [$topic['id'], $pager['perPage'], $pager['offset']]
);
$pageTitle = Helpers::e($topic['title']) . ' — Forum — ' . Config::get('club_name');
?>
<div class="container forum-wrap" style="padding-top:2rem">
  <nav class="breadcrumb" style="margin-bottom:1.5rem">
    <a href="/forum">Forum</a> <span>›</span>
    <a href="/forum/categorie/<?= Helpers::e($cat['slug']) ?>"><?= Helpers::e($cat['name']) ?></a>
    <span>›</span> <span><?= Helpers::e(Helpers::excerpt($topic['title'], 40)) ?></span>
  </nav>

  <div class="topic-header">
    <h1 class="topic-page-title">
      <?= $topic['pinned'] ? '📌 ' : '' ?><?= $topic['locked'] ? '🔒 ' : '' ?>
      <?= Helpers::e($topic['title']) ?>
    </h1>
    <div class="topic-page-meta">
      <?= $total ?> message<?= $total > 1 ? 's' : '' ?> · <?= $topic['views'] ?> vues
    </div>
  </div>

  <?php if (isset($formError)): ?>
    <div class="alert alert-error"><?= Helpers::e($formError) ?></div>
  <?php endif; ?>

  <!-- Posts -->
  <div class="posts-list">
    <?php foreach ($posts as $post): ?>
    <div class="post-item" id="post-<?= $post['id'] ?>">
      <div class="post-author">
        <?php if ($post['avatar']): ?>
          <img src="<?=asset(Helpers::e($post['avatar']))?>" class="post-avatar" alt="">
        <?php else: ?>
          <div class="post-avatar-placeholder">
            <?= mb_strtoupper(mb_substr($post['firstname'], 0, 1) . mb_substr($post['lastname'], 0, 1)) ?>
          </div>
        <?php endif; ?>
        <div class="post-author-name"><?= Helpers::e($post['firstname'] . ' ' . $post['lastname']) ?></div>
        <span class="role-badge role-<?= $post['role'] ?>"><?= Auth::ROLE_LABELS[$post['role']] ?></span>
        <div class="post-author-stats">
          <?= $post['post_count'] ?> message<?= $post['post_count'] > 1 ? 's' : '' ?>
        </div>
      </div>
      <div class="post-body">
        <div class="post-date">
          <a href="#post-<?= $post['id'] ?>"><?= Helpers::dateTimeFormat($post['created_at']) ?></a>
          <?php if ($post['edited_at']): ?>
            <span class="post-edited">modifié <?= Helpers::timeAgo($post['edited_at']) ?></span>
          <?php endif; ?>
        </div>
        <div class="post-content"><?= nl2br(Helpers::e($post['content'])) ?></div>
        <?php if (Auth::isAdmin() || Auth::id() === (int)$post['user_id']): ?>
        <div class="post-actions">
          <?php if (Auth::id() === (int)$post['user_id'] && !$topic['locked']): ?>
            <button class="post-action-btn" onclick="editPost(<?= $post['id'] ?>, this)">✏️ Modifier</button>
          <?php endif; ?>
          <?php if (Auth::isAdmin()): ?>
            <button class="post-action-btn danger" onclick="deletePost(<?= $post['id'] ?>)">🗑️ Supprimer</button>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($pager['pages'] > 1): ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $pager['pages']; $i++): ?>
      <a href="<?=u('/forum?page='.$i)?>" class="page-btn <?= $i === $pager['page'] ? 'active' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

  <!-- Formulaire de réponse -->
  <div id="bottom"></div>
  <?php if (!Auth::check()): ?>
    <div class="alert alert-info" style="margin-top:2rem">
      <a href="<?=u('/login')?>">Connectez-vous</a> pour répondre à ce sujet.
    </div>
  <?php elseif (($topic['reply_mode'] ?? 'members') === 'admin' && !Auth::isAdmin()): ?>
    <div class="card" style="text-align:center;color:var(--color-muted);padding:1.5rem">🔒 Seuls les administrateurs peuvent répondre à ce sujet.</div>
  <?php elseif (($topic['reply_mode'] ?? 'members') === 'members' && !Auth::check()): ?>
    <div class="card" style="text-align:center;color:var(--color-muted);padding:1.5rem">🔑 <a href="<?=u('/login')?>">Connectez-vous</a> pour répondre à ce sujet.</div>
  <?php elseif ($topic['locked']): ?>
    <div class="alert alert-warning" style="margin-top:2rem">🔒 Ce sujet est verrouillé.</div>
  <?php else: ?>
    <div class="reply-form card" style="margin-top:2rem">
      <h3 style="margin-bottom:1rem;font-size:1rem;font-weight:700">✏️ Votre réponse</h3>
      <form method="post">
        <?= Auth::csrfField() ?>
        <div class="form-group">
          <textarea name="content" rows="5" placeholder="Écrivez votre message..." required
                    style="font-size:.9rem"><?= isset($formError) ? Helpers::e($_POST['content'] ?? '') : '' ?></textarea>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:.75rem">
          <button type="submit" class="btn btn-primary">📤 Publier la réponse</button>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <!-- Boutons admin -->
  <?php if (Auth::isAdmin()): ?>
  <div class="admin-topic-actions card" style="margin-top:1rem">
    <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;margin-bottom:.75rem">⚙️ Administration du topic</div>
    <div style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:center;margin-bottom:1rem">
      <button class="btn btn-sm" onclick="toggleLock(<?= $topic['id'] ?>, <?= $topic['locked'] ?>)">
        <?= $topic['locked'] ? '🔓 Déverrouiller' : '🔒 Verrouiller' ?>
      </button>
      <button class="btn btn-sm" onclick="togglePin(<?= $topic['id'] ?>, <?= $topic['pinned'] ?>)">
        <?= $topic['pinned'] ? '📌 Désépingler' : '📌 Épingler' ?>
      </button>
      <button class="btn btn-sm danger" onclick="deleteTopic(<?= $topic['id'] ?>)">🗑️ Supprimer le sujet</button>
    </div>
    <!-- Contrôles d'accès -->
    <form method="post" style="display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;padding-top:.875rem;border-top:1px solid #f1f5f9">
      <?=Auth::csrfField()?>
      <input type="hidden" name="update_topic_access" value="1">
      <input type="hidden" name="topic_id" value="<?=$topic['id']?>">
      <div>
        <label style="display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:.3rem">👁 Qui peut lire</label>
        <select name="read_mode" style="border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem .75rem;font-size:.875rem;font-family:inherit">
          <option value="public"  <?=($topic['read_mode']??'public')==='public'?'selected':''?>>Tout le monde</option>
          <option value="members" <?=($topic['read_mode']??'public')==='members'?'selected':''?>>Membres uniquement</option>
        </select>
      </div>
      <div>
        <label style="display:block;font-size:.72rem;font-weight:700;text-transform:uppercase;color:#64748b;margin-bottom:.3rem">✏️ Qui peut répondre</label>
        <select name="reply_mode" style="border:1.5px solid #e2e8f0;border-radius:8px;padding:.4rem .75rem;font-size:.875rem;font-family:inherit">
          <option value="members" <?=($topic['reply_mode']??'members')==='members'?'selected':''?>>Membres uniquement</option>
          <option value="all"     <?=($topic['reply_mode']??'members')==='all'?'selected':''?>>Tout le monde</option>
          <option value="admin"   <?=($topic['reply_mode']??'members')==='admin'?'selected':''?>>Admins seulement</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">Appliquer</button>
    </form>
  </div>
  <?php endif; ?>

</div>

<?php elseif ($action === 'nouveau'): ?>
<!-- ═══════════════════════════════════════ NOUVEAU TOPIC -->
<?php Auth::require('member'); ?>
<div class="container forum-wrap" style="max-width:720px;padding-top:2rem">
  <nav class="breadcrumb" style="margin-bottom:1.5rem">
    <a href="/forum">Forum</a> <span>›</span> <span>Nouveau sujet</span>
  </nav>
  <h1 class="forum-title" style="font-size:2rem;margin-bottom:1.5rem">✏️ Nouveau sujet</h1>

  <?php if (isset($formError)): ?>
    <div class="alert alert-error"><?= Helpers::e($formError) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="post">
      <?= Auth::csrfField() ?>
      <div class="form-group">
        <label>Catégorie *</label>
        <select name="category_id" required>
          <option value="">Choisir une catégorie…</option>
          <?php
          $cats = Database::all("SELECT * FROM cc_forum_categories ORDER BY `order` ASC");
          foreach ($cats as $c):
          ?>
            <option value="<?= $c['id'] ?>" <?= (($_GET['cat'] ?? '') == $c['id']) ? 'selected' : '' ?>>
              <?= Helpers::e($c['icon'] ?? '') ?> <?= Helpers::e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Titre du sujet *</label>
        <input type="text" name="title" value="<?= Helpers::e($_POST['title'] ?? '') ?>"
               placeholder="Résumez votre question en quelques mots" required minlength="3" maxlength="200">
      </div>
      <div class="form-group">
        <label>Message *</label>
        <textarea name="content" rows="8"
                  placeholder="Détaillez votre message ici…" required minlength="10"><?= Helpers::e($_POST['content'] ?? '') ?></textarea>
      </div>
      <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:1rem">
        <a href="/forum" class="btn btn-ghost">Annuler</a>
        <button type="submit" class="btn btn-primary">📤 Publier le sujet</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- JS Forum -->
<script>
async function deletePost(id) {
  confirm_action('Supprimer ce message définitivement ?', async () => {
    const res = await apiRequest('/api/forum/post/' + id, 'DELETE', {csrf: '<?= Auth::csrfToken() ?>'});
    if (res.success) { document.getElementById('post-' + id)?.remove(); Toast.show('Message supprimé', 'success'); }
    else Toast.show(res.error || 'Erreur', 'error');
  });
}
async function deleteTopic(id) {
  confirm_action('Supprimer ce sujet et tous ses messages ?', async () => {
    const res = await apiRequest('/api/forum/topic/' + id, 'DELETE', {csrf: '<?= Auth::csrfToken() ?>'});
    if (res.success) window.location.href = '/forum';
    else Toast.show(res.error || 'Erreur', 'error');
  });
}
async function toggleLock(id, locked) {
  const res = await apiRequest('/api/forum/topic/' + id + '/lock', 'POST', {csrf: '<?= Auth::csrfToken() ?>'});
  if (res.success) location.reload();
}
async function togglePin(id, pinned) {
  const res = await apiRequest('/api/forum/topic/' + id + '/pin', 'POST', {csrf: '<?= Auth::csrfToken() ?>'});
  if (res.success) location.reload();
}
function editPost(id, btn) {
  const body = btn.closest('.post-body');
  const content = body.querySelector('.post-content');
  const original = content.innerText;
  content.innerHTML = `<textarea style="width:100%;min-height:100px;padding:.5rem;border:1.5px solid var(--color-border);border-radius:6px">${original}</textarea>
    <div style="display:flex;gap:.5rem;margin-top:.5rem;justify-content:flex-end">
      <button class="btn btn-sm" onclick="cancelEdit(this, \`${original.replace(/`/g,'\\`')}\`)">Annuler</button>
      <button class="btn btn-sm btn-primary" onclick="saveEdit(${id}, this)">💾 Enregistrer</button>
    </div>`;
  btn.style.display = 'none';
}
function cancelEdit(btn, original) {
  const body = btn.closest('.post-body');
  body.querySelector('.post-content').textContent = original;
  body.querySelector('.post-action-btn')?.style && (body.querySelector('.post-action-btn').style.display = '');
}
async function saveEdit(id, btn) {
  const content = btn.closest('.post-body').querySelector('textarea').value;
  const res = await apiRequest('/api/forum/post/' + id, 'PATCH', {content, csrf: '<?= Auth::csrfToken() ?>'});
  if (res.success) location.reload();
  else Toast.show(res.error || 'Erreur', 'error');
}
</script>

<style>
.forum-hero{background:linear-gradient(135deg,var(--color-primary),color-mix(in srgb,var(--color-primary) 70%,#000));padding:3rem 0;color:#fff;margin-bottom:2rem}
.forum-title{font-family:var(--font-heading);font-size:clamp(2rem,4vw,3rem);letter-spacing:.08em;margin-bottom:.25rem}
.forum-subtitle{opacity:.8;font-size:1rem}
.forum-wrap{padding-bottom:4rem}
.forum-topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem}
.breadcrumb{display:flex;align-items:center;gap:.4rem;font-size:.85rem;color:rgba(255,255,255,.7);margin-bottom:.75rem}
.breadcrumb a{color:rgba(255,255,255,.8);text-decoration:none}
.breadcrumb a:hover{color:#fff}
.forum-category{display:flex;align-items:center;gap:1.5rem;padding:1.25rem 1.5rem;margin-bottom:.75rem;transition:all .2s}
.forum-category:hover{box-shadow:var(--shadow-md)}
.fc-icon{font-size:2rem;flex-shrink:0;width:40px;text-align:center}
.fc-info{flex:1;min-width:0}
.fc-name{font-weight:700;font-size:1.05rem;color:var(--color-text);text-decoration:none;display:block;margin-bottom:.2rem}
.fc-name:hover{color:var(--color-primary)}
.fc-desc{color:var(--color-muted);font-size:.85rem}
.fc-stats{display:flex;gap:1.5rem;text-align:center;flex-shrink:0}
.fc-stats>div{display:flex;flex-direction:column;gap:.1rem}
.fc-stats strong{font-size:1.1rem;font-weight:700}
.fc-stats span{font-size:.7rem;color:var(--color-muted);text-transform:uppercase}
.fc-last{min-width:140px;text-align:right;flex-shrink:0}
.fc-last-date{font-size:.75rem;color:var(--color-muted);margin-bottom:.2rem}
.fc-last-topic{font-size:.8rem;font-weight:500;color:var(--color-primary);display:block;text-decoration:none}
.fc-last-topic:hover{text-decoration:underline}
.fc-last-by{font-size:.72rem;color:var(--color-muted)}
.topic-list{display:flex;flex-direction:column;gap:.5rem}
.topic-item{display:flex;align-items:center;gap:1rem;background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-sm);padding:1rem 1.25rem;transition:all .2s}
.topic-item:hover{border-color:var(--color-primary);box-shadow:var(--shadow-sm)}
.topic-item.pinned{border-left:3px solid var(--color-secondary)}
.topic-item.locked{opacity:.75}
.topic-icon{font-size:1.2rem;flex-shrink:0;width:28px;text-align:center}
.topic-info{flex:1;min-width:0}
.topic-title{font-weight:600;color:var(--color-text);text-decoration:none;display:block;margin-bottom:.15rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.topic-title:hover{color:var(--color-primary)}
.topic-meta{font-size:.78rem;color:var(--color-muted)}
.topic-stats{display:flex;gap:1.25rem;text-align:center;flex-shrink:0}
.topic-stats>div{display:flex;flex-direction:column}
.ts-val{font-weight:700;font-size:.95rem}
.ts-lab{font-size:.65rem;color:var(--color-muted);text-transform:uppercase}
.topic-last{min-width:110px;text-align:right;flex-shrink:0}
.tl-date{font-size:.75rem;color:var(--color-muted)}
.tl-by{font-size:.72rem;color:var(--color-muted)}
.topic-header{margin-bottom:1.5rem}
.topic-page-title{font-family:var(--font-heading);font-size:clamp(1.5rem,3vw,2.2rem);letter-spacing:.05em;margin-bottom:.3rem}
.topic-page-meta{color:var(--color-muted);font-size:.85rem}
.posts-list{display:flex;flex-direction:column;gap:1px}
.post-item{display:flex;background:#fff;border:1px solid var(--color-border);border-radius:var(--radius-sm);overflow:hidden}
.post-item+.post-item{border-top:none;border-radius:0}
.post-item:first-child{border-radius:var(--radius-sm) var(--radius-sm) 0 0}
.post-item:last-child{border-radius:0 0 var(--radius-sm) var(--radius-sm)}
.post-author{width:160px;flex-shrink:0;padding:1.25rem 1rem;background:var(--color-surface);border-right:1px solid var(--color-border);text-align:center;display:flex;flex-direction:column;align-items:center;gap:.35rem}
.post-avatar{width:54px;height:54px;border-radius:50%;object-fit:cover;border:2px solid var(--color-border)}
.post-avatar-placeholder{width:54px;height:54px;border-radius:50%;background:var(--color-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700}
.post-author-name{font-weight:700;font-size:.85rem}
.post-author-stats{font-size:.72rem;color:var(--color-muted)}
.post-body{flex:1;padding:1.25rem 1.5rem;min-width:0}
.post-date{font-size:.78rem;color:var(--color-muted);margin-bottom:.75rem;display:flex;gap:.75rem}
.post-date a{color:var(--color-muted);text-decoration:none}
.post-date a:hover{color:var(--color-primary)}
.post-edited{font-style:italic}
.post-content{line-height:1.75;font-size:.925rem;word-break:break-word;white-space:pre-wrap}
.post-actions{margin-top:.75rem;display:flex;gap:.5rem}
.post-action-btn{background:none;border:none;cursor:pointer;font-size:.78rem;color:var(--color-muted);padding:.2rem .4rem;border-radius:4px;transition:all .2s}
.post-action-btn:hover{background:var(--color-surface);color:var(--color-text)}
.post-action-btn.danger:hover{background:#fee2e2;color:var(--color-error)}
.reply-form{padding:1.5rem}
.btn-ghost{display:inline-flex;align-items:center;gap:.4rem;padding:.65rem 1.5rem;border-radius:var(--radius-sm);font-weight:600;font-size:.9rem;cursor:pointer;border:1.5px solid var(--color-border);background:#fff;color:var(--color-text);font-family:var(--font-body);transition:all .2s;text-decoration:none}
.btn-ghost:hover{border-color:var(--color-primary);color:var(--color-primary)}
.btn-sm{padding:.4rem .85rem;font-size:.8rem}
.admin-topic-actions{padding:1rem 1.25rem}
.btn.danger{background:#fee2e2;color:var(--color-error);border:1px solid #fecaca}
@media(max-width:768px){
  .forum-category{flex-wrap:wrap}.fc-last{display:none}
  .topic-item{flex-wrap:wrap}.topic-last,.topic-stats{display:none}
  .post-author{width:80px;padding:.75rem .5rem}.post-author-stats{display:none}
}
</style>

<?php
$content = ob_get_clean();
include CC_ROOT . '/templates/layout.php';
