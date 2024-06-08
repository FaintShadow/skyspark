import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import chat from "./chat.js";

window.onload = function () {

    const dialogue = document.querySelector("#chat-dialogue");

    if (document.querySelector("#chat-dialogue > span")){
        dialogue.classList.add('dialogue-empty');
    }

    if (document.querySelector("#chat-prompt")) {
        new chat();
    }
}


