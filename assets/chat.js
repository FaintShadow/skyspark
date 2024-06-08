import {marked} from "./marked.esm.js";
import axios from './axios.js';

export default class chat {
    constructor() {
        this.inputprompt = document.querySelector("#chat-prompt");
        this.dialogue = document.querySelector("#chat-dialogue");
        this.button = document.querySelector("#send");

        this.button.onclick = (e) => {
            this.prepareRequest();
        }
    }

    async prepareRequest() {
        const userPrompt = this.inputprompt.value;
        this.inputprompt.value = "";
        this.renderResultsHTML(userPrompt, "You");

        var response = await this.sendRequest(userPrompt);

        const text_response = response.data['response'];
        this.renderResultsHTML(text_response, "Oorbot");
    }


    async sendRequest(userPrompt) {
        return await axios.post(`https://${document.location.hostname}:8000/discussion/response/` + document.location.pathname.split('/')[2],
            {
                prompt: userPrompt
            },
            {
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                }
            }
        );
    }

    renderResultsHTML(text, user) {
        if (text.length > 0) {
            const message = document.createElement("div");
            message.classList.add("message");
            message.classList.add("row");

            const renderer = new marked.Renderer();
            renderer.paragraph = (text) => text;

            message.innerHTML =
                '<div class="row">' +
                '<div class="info w-100per">' +
                '<div class="usertime row">' +
                '<div>' +
                '<span class="username f-sb">' +
                user +
                '</span>' +
                '<span class="time f-m">now</span>' +
                '</div>' +
                '<span>Copy</span>' +
                '</div>' +
                '<p class="text f-m">' +
                marked(text, { renderer: renderer }) +
                '</p>' +
                '</div>' +
                '</div>';

            this.dialogue.appendChild(message);
        }
    }
}