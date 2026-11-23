
<script setup>
import { computed, nextTick, ref } from 'vue'
import { api } from '@/api/http'

const isOpen = ref(false)
const message = ref('')
const loading = ref(false)
const chatBody = ref(null)

const welcome = "Welcome to Made Exclusif! I'm your fragrance assistant. Ask me about perfumes, opening hours, delivery or anything else about our store."

const messages = ref([
  { role: 'assistant', content: welcome }
])

const canSend = computed(
  () => message.value.trim().length > 0 && !loading.value
)

async function scrollToBottom() {
  await nextTick()
  if (chatBody.value) {
    chatBody.value.scrollTop = chatBody.value.scrollHeight
  }
}

function toggleChat() {
  isOpen.value = !isOpen.value
  if (isOpen.value) scrollToBottom()
}

async function sendMessage() {
  const question = message.value.trim()
  if (!question || loading.value) return

  const history = messages.value
    .slice(1)
    .slice(-10)
    .map(({ role, content }) => ({ role, content }))

  messages.value.push({ role: 'user', content: question })
  message.value = ''
  loading.value = true
  await scrollToBottom()

  try {
    const response = await api('/ai/chat', {
      method: 'POST',
      body: {
        message: question,
        history
      }
    })

    messages.value.push({
      role: 'assistant',
      content: response.reply || 'I could not answer that question.'
    })
  } catch (error) {
    console.error('AI chat error:', error)
    messages.value.push({
      role: 'assistant',
      content: 'The assistant is temporarily unavailable. Please try again.'
    })
  } finally {
    loading.value = false
    await scrollToBottom()
  }
}
</script>

<template>
  <div class="mx-chat">
    <section
      v-if="isOpen"
      class="chat-panel"
      role="dialog"
      aria-label="Made Exclusif AI Assistant"
    >
      <header class="chat-header">
        <div>
          <strong>✦ Made Exclusif AI</strong>
          <small>Your personal fragrance assistant</small>
        </div>
        <button
          type="button"
          aria-label="Close chat"
          @click="toggleChat"
        >×</button>
      </header>

      <div
        ref="chatBody"
        class="chat-messages"
        role="log"
        aria-label="Chat conversation"
        aria-live="polite"
      >
        <div
          v-for="(item, index) in messages"
          :key="index"
          class="chat-bubble"
          :class="item.role"
        >
          {{ item.content }}
        </div>

        <div v-if="loading" class="chat-bubble assistant">
          Thinking...
        </div>
      </div>

      <form class="chat-form" @submit.prevent="sendMessage">
        <input
          v-model="message"
          type="text"
          placeholder="Type your question..."
          aria-label="Type your question"
          maxlength="1000"
          :disabled="loading"
        />
        <button
          type="submit"
          aria-label="Send message"
          :disabled="!canSend"
        >➤</button>
      </form>

      <footer>Made Exclusif AI Assistant</footer>
    </section>

    <button
      class="chat-launcher"
      type="button"
      :aria-label="isOpen ? 'Close chat' : 'Open AI chat'"
      :aria-expanded="isOpen"
      @click="toggleChat"
    >
      {{ isOpen ? '×' : '✦' }}
    </button>
  </div>
</template>

<style scoped>
.mx-chat {
  position: fixed;
  right: 24px;
  bottom: 24px;
  z-index: 9999;
  font-family: Arial, sans-serif;
}

.chat-launcher {
  width: 60px;
  height: 60px;
  border: 0;
  border-radius: 50%;
  background: #211b19;
  color: #e8c49a;
  font-size: 28px;
  cursor: pointer;
  box-shadow: 0 6px 24px #0003;
}

.chat-panel {
  display: flex;
  flex-direction: column;
  width: min(380px, calc(100vw - 32px));
  height: min(540px, calc(100dvh - 110px));
  margin-bottom: 14px;
  overflow: hidden;
  border: 1px solid #e7e1db;
  border-radius: 18px;
  background: white;
  box-shadow: 0 12px 48px #0003;
}

.chat-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px;
  background: #211b19;
  color: white;
}

.chat-header strong,
.chat-header small {
  display: block;
}

.chat-header small {
  margin-top: 6px;
  color: #d8c8b8;
  font-size: 11px;
}

.chat-header button {
  border: 0;
  background: transparent;
  color: white;
  font-size: 28px;
  cursor: pointer;
}

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  background: #faf8f6;
}

.chat-bubble {
  max-width: 86%;
  margin-bottom: 12px;
  padding: 12px;
  border-radius: 14px;
  font-size: 13px;
  line-height: 1.6;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.chat-bubble.assistant {
  margin-right: auto;
  background: white;
  color: #29211d;
  border: 1px solid #ece5de;
}

.chat-bubble.user {
  margin-left: auto;
  background: #211b19;
  color: white;
}

.chat-form {
  display: flex;
  gap: 8px;
  padding: 12px;
  border-top: 1px solid #eee;
}

.chat-form input {
  flex: 1;
  min-width: 0;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 9px;
  font: inherit;
  font-size: 13px;
}

.chat-form button {
  width: 42px;
  border: 0;
  border-radius: 9px;
  background: #211b19;
  color: #e8c49a;
  cursor: pointer;
}

.chat-form button:disabled {
  opacity: .4;
  cursor: not-allowed;
}

.chat-panel footer {
  padding: 0 12px 10px;
  color: #888;
  font-size: 10px;
  text-align: center;
}

@media (max-width: 480px) {
  .mx-chat {
    right: 16px;
    bottom: 16px;
  }
}
</style>

