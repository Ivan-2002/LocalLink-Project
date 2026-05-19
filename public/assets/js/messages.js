// public/assets/js/messages.js
// ============================================================
// Handles:
//  - Loading conversations list
//  - Opening a chat window
//  - Sending messages
//  - Auto-refreshing messages every 5s
//  - Notification bell polling every 10s
// ============================================================

$(function () {
  const API = BASE_URL + "../api/messages/";
  let activePartnerId = 0;
  let pollInterval = null;

  // ══════════════════════════════════════════════
  // CONVERSATIONS LIST
  // ══════════════════════════════════════════════
  function loadConversations() {
    $.get(API + "get-conversations.php")
      .done((res) => {
        if (!res.success) return;
        renderConversations(res.conversations);

        // If coming from product page — auto-open that chat
        if (OPEN_PARTNER && activePartnerId === 0) {
          openChat(OPEN_PARTNER);
        }
      })
      .fail(() => {
        $("#convList").html(
          '<div class="msg-loading text-danger">Failed to load.</div>',
        );
      });
  }

  function renderConversations(convs) {
    const list = $("#convList");

    if (!convs.length) {
      list.html('<div class="msg-loading">No conversations yet.</div>');
      return;
    }

    const html = convs
      .map((c) => {
        const unreadHtml =
          c.unread_count > 0
            ? `<span class="msg-unread-badge">${c.unread_count}</span>`
            : "";

        return `
        <div class="msg-conv-item ${c.partner_id == activePartnerId ? "active" : ""}"
             data-partner="${c.partner_id}"
             data-name="${esc(c.partner_name)}">
          <div class="msg-conv-avatar">${esc(c.partner_initial)}</div>
          <div class="msg-conv-info">
            <div class="msg-conv-name">${esc(c.partner_name)}</div>
            <div class="msg-conv-preview">${esc(c.last_message)}</div>
          </div>
          <div class="msg-conv-meta">
            <span class="msg-conv-time">${c.time_formatted}</span>
            ${unreadHtml}
          </div>
        </div>`;
      })
      .join("");

    list.html(html);
  }

  // Click on a conversation
  $(document).on("click", ".msg-conv-item", function () {
    const partnerId = $(this).data("partner");
    openChat(partnerId);
  });

  // ══════════════════════════════════════════════
  // OPEN CHAT
  // ══════════════════════════════════════════════
  function openChat(partnerId) {
    activePartnerId = partnerId;

    // Update active state in sidebar
    $(".msg-conv-item").removeClass("active");
    $(`.msg-conv-item[data-partner="${partnerId}"]`).addClass("active");

    // Show chat window
    $("#msgEmptyState").addClass("d-none");
    $("#msgChatInner").removeClass("d-none");

    // Load messages
    loadMessages(partnerId);

    // Start auto-refresh every 5 seconds
    clearInterval(pollInterval);
    pollInterval = setInterval(() => {
      if (activePartnerId) loadMessages(activePartnerId, true);
    }, 5000);
  }

  // ══════════════════════════════════════════════
  // LOAD MESSAGES
  // ══════════════════════════════════════════════
  function loadMessages(partnerId, silent = false) {
    if (!silent) {
      $("#msgBody").html('<div class="msg-loading">Loading messages...</div>');
    }

    $.get(API + "get-messages.php", { partner_id: partnerId }).done((res) => {
      if (!res.success) return;

      // Update header
      $("#chatPartnerAvatar").text(res.partner.initial);
      $("#chatPartnerName").text(res.partner.name);

      renderMessages(res.messages, res.partner);

      // Reload conversations to clear unread badge
      loadConversations();
    });
  }

  function renderMessages(messages, partner) {
    const body = $("#msgBody");
    if (!messages.length) {
      body.html(
        '<div class="msg-loading">No messages yet. Say hello! 👋</div>',
      );
      return;
    }

    let html = "";
    let lastDate = "";
    let lastSender = null;

    messages.forEach((m) => {
      const msgDate = new Date(m.created_at).toLocaleDateString("en-ZA", {
        weekday: "long",
        day: "numeric",
        month: "short",
      });

      // Date separator
      if (msgDate !== lastDate) {
        html += `<div class="msg-date-sep">${msgDate}</div>`;
        lastDate = msgDate;
        lastSender = null; // reset so name shows again after date change
      }

      const isMine = m.is_mine;
      const side = isMine ? "mine" : "theirs";

      // Show sender name / "You" label only at start of a group
      if (lastSender !== m.sender_id) {
        if (isMine) {
          html += `<div class="msg-you-label">You</div>`;
        } else {
          html += `<div class="msg-sender-label">${esc(partner.name)}</div>`;
        }
        lastSender = m.sender_id;
      }

      html += `
        <div class="msg-bubble-wrap ${side}">
          <div class="msg-bubble">${esc(m.body)}</div>
          <span class="msg-bubble-time">${m.time_formatted}</span>
        </div>`;
    });

    body.html(html);

    // Scroll to bottom
    body.scrollTop(body[0].scrollHeight);
  }

  // ══════════════════════════════════════════════
  // SEND MESSAGE
  // ══════════════════════════════════════════════
  function sendMessage() {
    const input = $("#msgInput");
    const body = input.val().trim();
    if (!body || !activePartnerId) return;

    const btn = $("#sendBtn");
    btn.prop("disabled", true);

    $.post(API + "send-message.php", {
      receiver_id: activePartnerId,
      body: body,
      product_id: OPEN_PRODUCT || "",
    })
      .done((res) => {
        if (res.success) {
          input.val("");
          loadMessages(activePartnerId, true);
        }
      })
      .always(() => btn.prop("disabled", false));
  }

  // Send on button click
  $("#sendBtn").on("click", sendMessage);

  // Send on Enter key
  $("#msgInput").on("keydown", function (e) {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  // Close chat
  $("#closeChatBtn").on("click", function () {
    activePartnerId = 0;
    clearInterval(pollInterval);
    $("#msgChatInner").addClass("d-none");
    $("#msgEmptyState").removeClass("d-none");
    $(".msg-conv-item").removeClass("active");
  });

  // ══════════════════════════════════════════════
  // NOTIFICATION BELL — polls every 10 seconds
  // This runs on EVERY page (injected via header.php)
  // ══════════════════════════════════════════════
  function pollNotifications() {
    $.get(BASE_URL + "../api/messages/get-unread-count.php").done((res) => {
      if (!res.success) return;
      const count = res.unread;
      const badge = $("#notifBadge");

      if (count > 0) {
        badge.text(count).removeClass("d-none");
      } else {
        badge.addClass("d-none").text("0");
      }
    });
  }

  // Bell click → go to messages page
  $("#bellWrap").on("click", function () {
    window.location.href = BASE_URL + "messages.php";
  });

  // Avatar dropdown
  $("#avatarToggle").on("click", function (e) {
    e.stopPropagation();
    $("#avatarDropdown").toggleClass("open");
  });
  $(document).on("click", () => $("#avatarDropdown").removeClass("open"));

  // ── Init ──────────────────────────────────────
  loadConversations();
  pollNotifications();
  setInterval(pollNotifications, 10000); // check every 10s

  // Helper
  function esc(str) {
    return String(str || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }
});
