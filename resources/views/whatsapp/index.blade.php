@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/whatsapp.css') }}">

    <section class="workspace-surface whatsapp-workspace rounded-3 p-3 p-lg-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 mb-1">WhatsApp</h1>
                <p class="text-muted-strong mb-0">
                    Send direct messages or approved templates from Fablead Task.
                </p>
            </div>

            <div class="whatsapp-status align-self-lg-start">
                <span class="badge text-bg-light border">Meta Cloud API</span>
            </div>
        </div>

        <div id="whatsapp-alert" class="alert d-none" role="status"></div>

        <div class="row g-4">
            <div class="col-xl-4">
                <div class="whatsapp-panel rounded-3 p-3">
                    <h2 class="h5 mb-3">Recipient</h2>

                    <div class="mb-3">
                        <label for="customer-select" class="form-label">
                            Customer
                        </label>
                        <select id="customer-select" class="form-select">
                            <option value="">Loading customers...</option>
                        </select>
                    </div>

                    <div id="customer-context" class="customer-context rounded-3 p-3 d-none">
                        <div class="fw-semibold" data-field="name"></div>
                        <div class="small text-muted-strong" data-field="email"></div>
                    </div>

                    <div class="mt-3">
                        <label for="whatsapp-to" class="form-label">
                            Recipient phone
                        </label>
                        <input id="whatsapp-to" class="form-control" type="tel" name="to" autocomplete="tel"
                            placeholder="+919876543210" required>
                        <div class="form-text">
                            Include the country code. Spaces and dashes are okay.
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <ul class="nav nav-tabs" id="whatsapp-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="message-tab" data-bs-toggle="tab" data-bs-target="#message-pane"
                            type="button" role="tab" aria-controls="message-pane" aria-selected="true">
                            Message
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="template-tab" data-bs-toggle="tab" data-bs-target="#template-pane"
                            type="button" role="tab" aria-controls="template-pane" aria-selected="false">
                            Template
                        </button>
                    </li>
                </ul>

                <div class="tab-content whatsapp-tab-content rounded-bottom-3 p-3 p-lg-4">
                    <div class="tab-pane fade show active" id="message-pane" role="tabpanel" aria-labelledby="message-tab"
                        tabindex="0">
                        <form id="whatsapp-message-form">
                            <div class="alert alert-danger form-errors d-none"></div>

                            <div class="mb-3">
                                <label for="whatsapp-message" class="form-label">
                                    Message
                                </label>
                                <textarea id="whatsapp-message" class="form-control" name="message" rows="8" maxlength="4096"
                                    placeholder="Write the WhatsApp message..." required></textarea>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button id="whatsapp-send-message" type="submit" class="btn btn-primary">
                                    Send Message
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="template-pane" role="tabpanel" aria-labelledby="template-tab"
                        tabindex="0">
                        <form id="whatsapp-template-form">
                            <div class="alert alert-danger form-errors d-none"></div>

                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="template-name" class="form-label">
                                        Template name
                                    </label>
                                    <input id="template-name" class="form-control" name="template_name"
                                        placeholder="hello_world" required>
                                </div>

                                <div class="col-md-4">
                                    <label for="language-code" class="form-label">
                                        Language code
                                    </label>
                                    <input id="language-code" class="form-control" name="language_code" value="en_US">
                                </div>
                            </div>

                            <div class="mt-3">
                                <label for="template-components" class="form-label">
                                    Components JSON
                                </label>
                                <textarea id="template-components" class="form-control font-monospace" name="components" rows="7"
                                    placeholder='[{"type":"body","parameters":[{"type":"text","text":"Customer"}]}]'></textarea>
                                <div class="form-text">
                                    Optional. Leave blank when the template has no variables.
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-3">
                                <button id="whatsapp-send-template" type="submit" class="btn btn-primary">
                                    Send Template
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('js/whatsapp.js') }}"></script>
@endpush
