<?php

declare(strict_types=1);
?>
                </section>
            </div>
        </div>
    </div>

    <?php if (isset($mobileModules) && is_array($mobileModules) && $mobileModules !== []): ?>
        <nav class="lg:hidden fixed inset-x-0 bottom-0 z-40 border-t border-slate-300 bg-white/95 backdrop-blur">
            <div class="grid grid-cols-<?= (int) count($mobileModules) ?>">
                <?php foreach ($mobileModules as $module): ?>
                    <?php $active = $currentRoute === (string) $module['route_key']; ?>
                    <a
                        href="<?= htmlspecialchars((string) $module['route'], ENT_QUOTES, 'UTF-8') ?>"
                        class="px-2 py-2 text-center text-[11px] font-semibold <?= $active ? 'text-slate-900 bg-slate-100' : 'text-slate-600' ?>"
                    >
                        <?= htmlspecialchars((string) $module['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
    <?php endif; ?>
</body>
</html>
