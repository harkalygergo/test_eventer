let authHeader = null;
let currentUser = null;
let conversations = [];
let activeConversationId = null;

async function api(path, options = {}) {
    if (!options.headers) {
        options.headers = {};
    }
    options.headers['Content-Type'] = 'application/json';
    if (authHeader) {
        options.headers['Authorization'] = authHeader;
    }

    const response = await fetch(`/api${path}`, options);
    if (response.status === 401) {
        throw new Error('Unauthorized');
    }
    if (response.status === 204) {
        return null;
    }
    return await response.json();
}

function showSection(idToShow) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));

    document.getElementById(`tab-${idToShow}`).classList.add('active');
    document.querySelector(`.tab[data-tab="${idToShow}"]`).classList.add('active');
}

async function loadEvents() {
    const list = document.getElementById('events-list');
    list.innerHTML = '';
    const events = await api('/events');
    events.forEach(ev => {
        const li = document.createElement('li');
        li.className = 'event-item';
        li.innerHTML = `
            <div class="event-main">
                <div class="event-title">${ev.title}</div>
                <div class="event-meta">${new Date(ev.occursAt).toLocaleString()}</div>
                <input class="event-description-input" type="text" placeholder="Description" value="${ev.description ?? ''}">
            </div>
            <div class="event-actions">
                <button data-action="save" data-id="${ev.id}">Save</button>
                <button data-action="delete" data-id="${ev.id}">Delete</button>
            </div>
        `;
        list.appendChild(li);
    });
}

async function loadConversations() {
    conversations = await api('/helpdesk/conversations');
    const list = document.getElementById('conversations-list');
    list.innerHTML = '';
    conversations.forEach(conv => {
        const li = document.createElement('li');
        li.className = 'event-item';
        li.innerHTML = `
            <div class="event-main">
                <div class="event-title">${conv.user}</div>
                <div class="event-meta">#${conv.id} • ${conv.status}</div>
            </div>
            <div class="event-actions">
                <button data-id="${conv.id}">Open</button>
            </div>
        `;
        list.appendChild(li);
    });
}

function renderConversation(conv, containerId) {
    const container = document.getElementById(containerId);
    container.innerHTML = '';
    conv.messages.forEach(msg => {
        const div = document.createElement('div');
        div.className = `chat-message ${msg.sender}`;
        const span = document.createElement('span');
        span.textContent = `${msg.sender}: ${msg.content}`;
        div.appendChild(span);
        container.appendChild(div);
    });
    container.scrollTop = container.scrollHeight;
}

document.addEventListener('DOMContentLoaded', () => {
    const loginForm = document.getElementById('login-form');
    const loginError = document.getElementById('login-error');
    const loginSection = document.getElementById('login-section');
    const appSection = document.getElementById('app-section');
    const passwordResetBtn = document.getElementById('password-reset-btn');

    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        loginError.textContent = '';
        const email = document.getElementById('login-email').value;
        const password = document.getElementById('login-password').value;

        authHeader = 'Basic ' + btoa(`${email}:${password}`);

        try {
            currentUser = await api('/me');
            loginSection.classList.add('hidden');
            appSection.classList.remove('hidden');
            await loadEvents();

            if (currentUser.roles.includes('ROLE_HELPDESK')) {
                document.getElementById('agent-tab').hidden = false;
                await loadConversations();
            }
        } catch (err) {
            authHeader = null;
            currentUser = null;
            loginError.textContent = 'Login failed. Please check your credentials.';
        }
    });

    passwordResetBtn.addEventListener('click', () => {
        alert('Password reset is not fully wired to email in this demo. Please contact an administrator to reset your password.');
    });

    document.querySelectorAll('.tab').forEach(btn => {
        btn.addEventListener('click', () => {
            const tab = btn.getAttribute('data-tab');
            showSection(tab);
            if (tab === 'events') {
                loadEvents();
            } else if (tab === 'agent' && currentUser.roles.includes('ROLE_HELPDESK')) {
                loadConversations();
            }
        });
    });

    document.getElementById('event-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const title = document.getElementById('event-title').value;
        const occursAt = document.getElementById('event-occurs-at').value;
        const description = document.getElementById('event-description').value;
        await api('/events', {
            method: 'POST',
            body: JSON.stringify({ title, occursAt, description }),
        });
        e.target.reset();
        await loadEvents();
    });

    document.getElementById('events-list').addEventListener('click', async (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const id = btn.getAttribute('data-id');
        const action = btn.getAttribute('data-action');

        if (action === 'delete') {
            await api(`/events/${id}`, { method: 'DELETE' });
            await loadEvents();
        } else if (action === 'save') {
            const li = btn.closest('.event-item');
            const descInput = li.querySelector('.event-description-input');
            await api(`/events/${id}`, {
                method: 'PATCH',
                body: JSON.stringify({ description: descInput.value }),
            });
        }
    });

    document.getElementById('chat-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('chat-input');
        const text = input.value;
        const conv = await api('/helpdesk/messages', {
            method: 'POST',
            body: JSON.stringify({ text }),
        });
        renderConversation(conv, 'chat-messages');
        input.value = '';
    });

    document.getElementById('conversations-list').addEventListener('click', (e) => {
        const btn = e.target.closest('button');
        if (!btn) return;
        const id = Number(btn.getAttribute('data-id'));
        const conv = conversations.find(c => c.id === id);
        if (!conv) return;
        activeConversationId = id;
        document.getElementById('agent-conversation').classList.remove('hidden');
        document.getElementById('agent-conversation-title').textContent = `Conversation #${id} with ${conv.user}`;
        renderConversation(conv, 'agent-messages');
    });

    document.getElementById('agent-reply-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!activeConversationId) return;
        const input = document.getElementById('agent-reply-input');
        const text = input.value;
        const conv = await api(`/helpdesk/conversations/${activeConversationId}/reply`, {
            method: 'POST',
            body: JSON.stringify({ text }),
        });
        const idx = conversations.findIndex(c => c.id === conv.id);
        if (idx !== -1) {
            conversations[idx] = conv;
        }
        renderConversation(conv, 'agent-messages');
        input.value = '';
    });
});

