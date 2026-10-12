@extends('layouts.app')

@section('content')
    <style>
        .ai-library {
            --ai-ink: #20332e;
            --ai-muted: #62746d;
            --ai-line: #dce6e0;
            color: var(--ai-ink);
        }

        .ai-library .library-kicker {
            display: flex;
            align-items: center;
            gap: .65rem;
            color: #327265;
            letter-spacing: .12em;
        }

        .ai-library .library-kicker::after {
            width: 2.5rem;
            border-top: 1px solid currentColor;
            content: "";
        }

        .ai-library header h1 {
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(2rem, 3vw, 2.6rem);
            font-weight: 600;
            letter-spacing: -.035em;
        }

        .ai-library .library-intro {
            max-width: 54rem;
            color: var(--ai-muted);
        }

        .ai-library .library-edition {
            background: #20332e;
            border-color: #20332e !important;
            color: #fff;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            letter-spacing: .035em;
        }

        .ai-library .security-note {
            border: 1px solid #d7e8df;
            border-left: 4px solid #327265;
            background: #f3f8f5;
        }

        .ai-library .catalog-controls {
            background: #f7f9f7;
            border: 1px solid var(--ai-line);
        }

        .ai-library #provider-grid {
            counter-reset: provider-entry;
        }

        .ai-library .provider-item {
            counter-increment: provider-entry;
        }

        .ai-library .provider-card {
            height: 100%;
            position: relative;
            border: 1px solid var(--ai-line);
            border-radius: .55rem !important;
            box-shadow: 0 4px 14px rgba(31, 55, 46, .035);
            transition: border-color .16s ease, box-shadow .16s ease, transform .16s ease;
        }

        .ai-library .provider-card::before {
            display: block;
            margin: -.15rem 0 .8rem;
            padding-bottom: .55rem;
            border-bottom: 1px solid var(--ai-line);
            color: #327265;
            content: "ENTRY / " counter(provider-entry, decimal-leading-zero);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: .65rem;
            font-weight: 700;
            letter-spacing: .13em;
        }

        .ai-library .provider-card h2 {
            font-family: Georgia, "Times New Roman", serif;
            font-weight: 600;
            letter-spacing: -.02em;
        }

        .ai-library .provider-card:hover {
            border-color: #a9c8bb;
            box-shadow: 0 12px 25px rgba(31, 55, 46, .085);
            transform: translateY(-2px);
        }

        .ai-library .provider-card:focus-within {
            outline: 3px solid rgba(50, 114, 101, .24);
            outline-offset: 2px;
        }

        .ai-library .provider-summary {
            color: var(--ai-muted);
            min-height: 3.25rem;
        }

        .ai-library .provider-use {
            border-top: 1px solid #edf1ee;
            color: #384b44;
        }

        .ai-library .cost-free {
            background: #e6f3ea;
            color: #205c38;
        }

        .ai-library .cost-paid {
            background: #eaf0f7;
            color: #344f70;
        }

        .ai-library .cost-local {
            background: #eef0ef;
            color: #45534d;
        }

        .ai-library .catalog-link {
            color: #28685c;
            font-weight: 600;
            text-decoration-thickness: 1px;
            text-underline-offset: 3px;
        }

        .ai-library .catalog-link:hover {
            color: #17483f;
        }

        .ai-library .catalog-link:focus-visible,
        .ai-library .form-control:focus,
        .ai-library .form-select:focus {
            outline: 3px solid rgba(50, 114, 101, .24);
            outline-offset: 2px;
        }

        .ai-library .flow-step {
            border-top: 2px solid #93b8a8;
        }

        @media (prefers-reduced-motion: reduce) {
            .ai-library .provider-card {
                transition: none;
            }

            .ai-library .provider-card:hover {
                transform: none;
            }
        }
    </style>

    <section class="workspace-surface ai-library rounded-3 p-3 p-lg-4" aria-labelledby="ai-library-title">
        <header class="mb-4">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                <div>
                    <p class="library-kicker text-uppercase fw-semibold small mb-2">A practical field guide</p>
                    <h1 id="ai-library-title" class="h2 mb-2">AI API Library</h1>
                    <p class="library-intro mb-0">
                        Compare tools for image understanding, OCR, invoice reading, and chat. Availability,
                        quotas, pricing, and regional access change—check each provider’s official documentation
                        before you build.
                    </p>
                </div>
                <div class="align-self-lg-start">
                    <span class="badge border px-3 py-2 library-edition">07 PROVIDERS / 03 COST MODELS</span>
                </div>
            </div>
        </header>

        <aside class="security-note rounded-3 p-3 p-lg-4 mb-4" aria-label="API billing and security note">
            <div class="row g-3 align-items-start">
                <div class="col-lg-8">
                    <h2 class="h6 mb-1">API access is separate from consumer chat subscriptions</h2>
                    <p class="small text-muted-strong mb-0">
                        A ChatGPT or Claude subscription does not include API usage. Keep provider keys secret;
                        make API calls from your backend—not browser code.
                    </p>
                </div>
                <div class="col-lg-4">
                    <p class="small mb-0"><strong>Free tiers are not forever guarantees.</strong> Limits vary by model,
                        account, location, and current terms.</p>
                </div>
            </div>
        </aside>

        <section aria-label="Search and filter AI providers">
            <div class="catalog-controls rounded-3 p-3 mb-3">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-6">
                        <label for="provider-search" class="form-label small fw-semibold mb-1">Search the catalog</label>
                        <input id="provider-search" class="form-control" type="search"
                            placeholder="Try “invoice”, “image”, or a provider name" autocomplete="off">
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="category-filter" class="form-label small fw-semibold mb-1">Use case</label>
                        <select id="category-filter" class="form-select">
                            <option value="all">All use cases</option>
                            <option value="ocr">OCR &amp; invoices</option>
                            <option value="image">Image</option>
                            <option value="chat">Chat &amp; LLM</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="cost-filter" class="form-label small fw-semibold mb-1">Cost model</label>
                        <select id="cost-filter" class="form-select">
                            <option value="all">All cost models</option>
                            <option value="free">Free to try</option>
                            <option value="paid">Paid API</option>
                            <option value="local">Run locally</option>
                        </select>
                    </div>
                </div>
            </div>
            <p id="provider-count" class="small text-muted-strong mb-3" role="status" aria-live="polite">
                Showing 7 providers
            </p>
        </section>

        <section aria-label="AI API providers">
            <div id="provider-grid" class="row g-3">
                <div class="col-md-6 col-xl-4 provider-item" data-provider="gemini google invoice image understanding generation multimodal"
                    data-categories="ocr image chat" data-cost="free">
                    <article class="provider-card rounded-3 p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <span class="badge text-bg-light border mb-2">Google · Multimodal</span>
                                <h2 class="h5 mb-0">Gemini API</h2>
                            </div>
                            <span class="badge cost-free">Limited free tier</span>
                        </div>
                        <p class="provider-summary small mb-3">
                            Selected models may have a limited free tier. Image understanding and invoice extraction are available;
                            image generation depends on the model.
                        </p>
                        <p class="provider-use small mb-3 pt-3">
                            <strong>Good for</strong> trying text, image, and structured extraction workflows in one API.
                        </p>
                        <div class="d-flex flex-wrap gap-3 small">
                            <a class="catalog-link" href="https://ai.google.dev/gemini-api/docs/pricing" target="_blank" rel="noopener noreferrer">Pricing &amp; quotas<span class="visually-hidden"> (opens in new tab)</span></a>
                            <a class="catalog-link" href="https://ai.google.dev/gemini-api/docs/image-understanding" target="_blank" rel="noopener noreferrer">Image guide<span class="visually-hidden"> (opens in new tab)</span></a>
                        </div>
                    </article>
                </div>

                <div class="col-md-6 col-xl-4 provider-item" data-provider="google cloud vision text document optical character recognition ocr invoice"
                    data-categories="ocr image" data-cost="free">
                    <article class="provider-card rounded-3 p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <span class="badge text-bg-light border mb-2">Google Cloud · OCR</span>
                                <h2 class="h5 mb-0">Cloud Vision</h2>
                            </div>
                            <span class="badge cost-free">Free monthly units*</span>
                        </div>
                        <p class="provider-summary small mb-3">
                            Hosted text and document OCR that reads characters; it does not extract structured invoice fields by itself.
                        </p>
                        <p class="provider-use small mb-3 pt-3">
                            <strong>Good for</strong> turning scanned pages or images into text for your own parsing step.
                        </p>
                        <div class="d-flex flex-wrap gap-3 small">
                            <a class="catalog-link" href="https://cloud.google.com/vision/pricing" target="_blank" rel="noopener noreferrer">Official pricing<span class="visually-hidden"> (opens in new tab)</span></a>
                        </div>
                        <p class="small text-muted-strong mt-3 mb-0">*First 1,000 monthly units are free for listed features; verify current terms.</p>
                    </article>
                </div>

                <div class="col-md-6 col-xl-4 provider-item" data-provider="azure microsoft document intelligence invoice extraction prebuilt"
                    data-categories="ocr" data-cost="free">
                    <article class="provider-card rounded-3 p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <span class="badge text-bg-light border mb-2">Microsoft Azure · Documents</span>
                                <h2 class="h5 mb-0">Document Intelligence</h2>
                            </div>
                            <span class="badge cost-free">Free F0 tier*</span>
                        </div>
                        <p class="provider-summary small mb-3">
                            A dedicated prebuilt invoice model designed to identify fields, line items, and document structure.
                        </p>
                        <p class="provider-use small mb-3 pt-3">
                            <strong>Good for</strong> invoice analysis where a document-focused extraction API fits.
                        </p>
                        <div class="d-flex flex-wrap gap-3 small">
                            <a class="catalog-link" href="https://learn.microsoft.com/en-us/azure/ai-services/document-intelligence/" target="_blank" rel="noopener noreferrer">Documentation<span class="visually-hidden"> (opens in new tab)</span></a>
                            <a class="catalog-link" href="https://azure.microsoft.com/pricing/details/document-intelligence/" target="_blank" rel="noopener noreferrer">Pricing<span class="visually-hidden"> (opens in new tab)</span></a>
                        </div>
                        <p class="small text-muted-strong mt-3 mb-0">*Availability and quotas can depend on region/account and current tier limits.</p>
                    </article>
                </div>

                <div class="col-md-6 col-xl-4 provider-item" data-provider="tesseract optical character recognition ocr local open source invoice text"
                    data-categories="ocr image" data-cost="local">
                    <article class="provider-card rounded-3 p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <span class="badge text-bg-light border mb-2">Open source · On-device</span>
                                <h2 class="h5 mb-0">Tesseract OCR</h2>
                            </div>
                            <span class="badge cost-local">Run locally</span>
                        </div>
                        <p class="provider-summary small mb-3">
                            Free OCR software you run yourself—not a hosted API. Extracted text still needs your own field parser.
                        </p>
                        <p class="provider-use small mb-3 pt-3">
                            <strong>Good for</strong> local text recognition when you want control over where documents are processed.
                        </p>
                        <div class="d-flex flex-wrap gap-3 small">
                            <a class="catalog-link" href="https://github.com/tesseract-ocr/tesseract" target="_blank" rel="noopener noreferrer">Project &amp; setup<span class="visually-hidden"> (opens in new tab)</span></a>
                        </div>
                    </article>
                </div>

                <div class="col-md-6 col-xl-4 provider-item" data-provider="openai chatgpt api text image vision invoice language model"
                    data-categories="ocr image chat" data-cost="paid">
                    <article class="provider-card rounded-3 p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <span class="badge text-bg-light border mb-2">OpenAI · Multimodal</span>
                                <h2 class="h5 mb-0">OpenAI API</h2>
                            </div>
                            <span class="badge cost-paid">Paid API</span>
                        </div>
                        <p class="provider-summary small mb-3">
                            Text and image analysis with ChatGPT models; API usage is billed separately from ChatGPT plans.
                        </p>
                        <p class="provider-use small mb-3 pt-3">
                            <strong>Good for</strong> conversational extraction, image Q&amp;A, and structured text workflows.
                        </p>
                        <div class="d-flex flex-wrap gap-3 small">
                            <a class="catalog-link" href="https://developers.openai.com/api/docs/pricing" target="_blank" rel="noopener noreferrer">API pricing<span class="visually-hidden"> (opens in new tab)</span></a>
                            <a class="catalog-link" href="https://developers.openai.com/api/docs/guides/images" target="_blank" rel="noopener noreferrer">Image guide<span class="visually-hidden"> (opens in new tab)</span></a>
                        </div>
                    </article>
                </div>

                <div class="col-md-6 col-xl-4 provider-item" data-provider="anthropic claude api text image document invoice vision language model"
                    data-categories="ocr image chat" data-cost="paid">
                    <article class="provider-card rounded-3 p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <span class="badge text-bg-light border mb-2">Anthropic · Multimodal</span>
                                <h2 class="h5 mb-0">Claude API</h2>
                            </div>
                            <span class="badge cost-paid">Paid API</span>
                        </div>
                        <p class="provider-summary small mb-3">
                            Text, image, and document understanding for tasks such as invoice extraction; check current pricing.
                        </p>
                        <p class="provider-use small mb-3 pt-3">
                            <strong>Good for</strong> reading document content and returning a structured interpretation.
                        </p>
                        <div class="d-flex flex-wrap gap-3 small">
                            <a class="catalog-link" href="https://docs.anthropic.com/en/docs/about-claude/pricing" target="_blank" rel="noopener noreferrer">API pricing<span class="visually-hidden"> (opens in new tab)</span></a>
                            <a class="catalog-link" href="https://docs.anthropic.com/en/docs/build-with-claude/vision" target="_blank" rel="noopener noreferrer">Vision guide<span class="visually-hidden"> (opens in new tab)</span></a>
                        </div>
                        <p class="small text-muted-strong mt-3 mb-0">API usage is billed separately from Claude consumer plans.</p>
                    </article>
                </div>

                <div class="col-md-6 col-xl-4 provider-item" data-provider="hugging face inference providers hosted models api credits"
                    data-categories="chat image" data-cost="paid">
                    <article class="provider-card rounded-3 p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <div>
                                <span class="badge text-bg-light border mb-2">Hugging Face · Model hub</span>
                                <h2 class="h5 mb-0">Inference Providers</h2>
                            </div>
                            <span class="badge cost-paid">Credits required</span>
                        </div>
                        <p class="provider-summary small mb-3">
                            A hosted inference catalog for models from multiple providers; free users have no included monthly credits.
                        </p>
                        <p class="provider-use small mb-3 pt-3">
                            <strong>Good for</strong> exploring hosted models behind a common model-catalog workflow.
                        </p>
                        <div class="d-flex flex-wrap gap-3 small">
                            <a class="catalog-link" href="https://huggingface.co/docs/inference-providers/pricing" target="_blank" rel="noopener noreferrer">Pricing &amp; credits<span class="visually-hidden"> (opens in new tab)</span></a>
                        </div>
                    </article>
                </div>
            </div>

            <div id="provider-empty" class="alert alert-light border d-none mt-3" role="status">
                No providers match those filters. Try another search or choose a different cost model.
            </div>
        </section>

        <section class="border rounded-3 p-3 p-lg-4 mt-4" aria-labelledby="invoice-flow-title">
            <div class="row g-3 align-items-start">
                <div class="col-lg-4">
                    <p class="library-kicker text-uppercase fw-semibold small mb-2">A safer starting point</p>
                    <h2 id="invoice-flow-title" class="h5 mb-2">Invoice extraction, in four checks</h2>
                    <p class="small text-muted-strong mb-0">
                        Treat invoices as sensitive data. Model accuracy is not guaranteed.
                    </p>
                </div>
                <div class="col-lg-8">
                    <ol class="row row-cols-1 row-cols-sm-2 g-3 list-unstyled mb-0">
                        <li class="col">
                            <div class="flow-step pt-2">
                                <span class="small fw-semibold">01 · Receive securely</span>
                                <p class="small text-muted-strong mb-0">Upload and process the document on your server.</p>
                            </div>
                        </li>
                        <li class="col">
                            <div class="flow-step pt-2">
                                <span class="small fw-semibold">02 · Specify fields</span>
                                <p class="small text-muted-strong mb-0">Ask for a clear JSON schema, such as supplier, date, and total.</p>
                            </div>
                        </li>
                        <li class="col">
                            <div class="flow-step pt-2">
                                <span class="small fw-semibold">03 · Validate</span>
                                <p class="small text-muted-strong mb-0">Check formats, required values, and arithmetic in your code.</p>
                            </div>
                        </li>
                        <li class="col">
                            <div class="flow-step pt-2">
                                <span class="small fw-semibold">04 · Human review</span>
                                <p class="small text-muted-strong mb-0">Let a person confirm the result before saving or acting on it.</p>
                            </div>
                        </li>
                    </ol>
                </div>
            </div>
        </section>
    </section>
@endsection

@push('scripts')
    <script>
        $(function() {
            const $items = $('.provider-item');
            const $search = $('#provider-search');
            const $category = $('#category-filter');
            const $cost = $('#cost-filter');

            function filterProviders() {
                const term = $search.val().trim().toLowerCase();
                const category = $category.val();
                const cost = $cost.val();
                let visible = 0;

                $items.each(function() {
                    const $item = $(this);
                    const matches = (!term || $item.text().toLowerCase().includes(term) ||
                            $item.data('provider').includes(term)) &&
                        (category === 'all' || $item.data('categories').split(' ').includes(category)) &&
                        (cost === 'all' || $item.data('cost') === cost);

                    $item.toggleClass('d-none', !matches);
                    visible += matches ? 1 : 0;
                });

                $('#provider-count').text(
                    visible === 1 ? 'Showing 1 provider' : `Showing ${visible} providers`
                );
                $('#provider-empty').toggleClass('d-none', visible !== 0);
            }

            $search.add($category).add($cost).on('input change', filterProviders);
        });
    </script>
@endpush
