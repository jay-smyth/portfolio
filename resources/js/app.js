// Add a "Copy" button to code blocks in rendered Markdown.
function addCopyButtons() {
    if (!navigator.clipboard) {
        return;
    }

    document.querySelectorAll('.markdown pre').forEach((pre) => {
        if (pre.parentElement.classList.contains('code-block')) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'code-block';
        pre.replaceWith(wrapper);
        wrapper.appendChild(pre);

        const button = document.createElement('button');
        button.type = 'button';
        button.dataset.copy = '';
        button.textContent = 'Copy';
        button.addEventListener('click', async () => {
            await navigator.clipboard.writeText(pre.innerText.trimEnd());
            button.textContent = 'Copied';
            setTimeout(() => (button.textContent = 'Copy'), 2000);
        });
        wrapper.appendChild(button);
    });
}

// Runs on first load and after every wire:navigate page change.
document.addEventListener('livewire:navigated', addCopyButtons);
