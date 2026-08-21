# -*- coding: utf-8 -*-
"""Genera el diagrama de flujo de datos del sistema TAG2 (PNG)."""
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
from matplotlib.patches import FancyBboxPatch, FancyArrowPatch
import textwrap

# ── Paleta ──────────────────────────────────────────────
C_CTRL   = "#DBEAFE"   # controladores (azul claro)
C_MODEL  = "#E0F2FE"   # modelos
C_OBS    = "#FDE68A"   # observers (amarillo)
C_EVENT  = "#FBCFE8"   # eventos (rosa)
C_LIST   = "#C7D2FE"   # listeners (índigo claro)
C_SVC    = "#A7F3D0"   # servicios (verde)
C_DTO    = "#E5E7EB"   # DTO
C_DB     = "#CBD5E1"   # base de datos
C_WS     = "#FCA5A5"   # websocket

def box(ax, x, y, w, h, text, fc, fs=9.5, weight="normal", ec="#334155"):
    """Dibuja una caja redondeada con texto centrado, con ajuste de línea."""
    ax.add_patch(FancyBboxPatch((x, y), w, h,
        boxstyle="round,pad=0.02,rounding_size=0.06",
        linewidth=1.2, edgecolor=ec, facecolor=fc, zorder=2))
    wrapped = textwrap.fill(text, width=30)
    ax.text(x + w/2, y + h/2, wrapped, ha="center", va="center",
            fontsize=fs, weight=weight, zorder=3)

def arrow(ax, x1, y1, x2, y2, color="#475569", style="-|>", lw=1.3, ls="-"):
    ax.add_patch(FancyArrowPatch((x1, y1), (x2, y2),
        arrowstyle=style, mutation_scale=14, lw=lw,
        color=color, linestyle=ls, zorder=1))

def label(ax, x, y, text, fs=7.6, color="#475569", ha="center"):
    ax.text(x, y, text, fontsize=fs, color=color, ha=ha, va="center",
            style="italic", zorder=4)

fig, ax = plt.subplots(figsize=(17.5, 23))
ax.set_xlim(0, 100)
ax.set_ylim(0, 132)
ax.axis("off")

# ── Título y leyenda ────────────────────────────────────
ax.text(50, 129, "TAG2 — Flujo de Datos del Ciclo Maestro Comercial",
        ha="center", fontsize=16, weight="bold", color="#0F172A")
legend = [
    ("Controller", C_CTRL), ("Modelo Eloquent", C_MODEL),
    ("Observer (emite eventos)", C_OBS), ("Evento de dominio", C_EVENT),
    ("Listener", C_LIST), ("Service/Estado", C_SVC), ("DTO CambioEstado", C_DTO),
    ("Base de datos", C_DB), ("WebSocket (Reverb)", C_WS),
]
lx = 2
ly = 126.5
for name, c in legend:
    ax.add_patch(FancyBboxPatch((lx, ly), 12, 1.6,
        boxstyle="round,pad=0.01,rounding_size=0.04",
        linewidth=0.8, edgecolor="#334155", facecolor=c))
    ax.text(lx + 6, ly + 0.8, name, ha="center", va="center", fontsize=6.8, color="#111827")
    lx += 9.6

# ── CICLO 1: ATENCIÓN ──────────────────────────────────
ax.text(3, 121, "1 · ATENCIÓN", fontsize=12, weight="bold", color="#1E40AF")

box(ax, 3, 112.5, 20, 6, "AtencionController\n::store()", C_CTRL)
box(ax, 30, 112.5, 20, 6, "Atencion::create()", C_MODEL)
box(ax, 57, 112.5, 22, 6, "AtencionObserver\n::created()/:updated()", C_OBS)
box(ax, 74, 96, 22, 6, "AtencionBroadcast\n(ShouldBroadcastNow)", C_EVENT)
box(ax, 74, 82, 22, 6, 'Canal privado\n"atenciones"', C_WS)

arrow(ax, 23, 115.5, 30, 115.5)
arrow(ax, 50, 115.5, 57, 115.5)
arrow(ax, 68, 112.5, 68, 102, color="#9F1239")
arrow(ax, 68, 102, 74, 99, color="#9F1239")
arrow(ax, 85, 96, 85, 88, color="#B91C1C")

label(ax, 26.5, 116.8, "HTTP POST /api/atenciones (auth:sanctum)")
label(ax, 53.5, 116.8, "Observer registrado")

# ── CICLO 2: COTIZACIÓN ────────────────────────────────
ax.text(3, 76, "2 · COTIZACIÓN", fontsize=12, weight="bold", color="#1E40AF")

box(ax, 3, 68, 20, 6, "CotizacionController\n::store() / ::update()", C_CTRL)
box(ax, 30, 68, 20, 6, "Cotizacion::create()\n/save()", C_MODEL)
box(ax, 57, 68, 22, 6, "CotizacionObserver\n::saved()", C_OBS)
box(ax, 57, 54, 22, 6, "event(CotizacionGuardado)", C_EVENT)
box(ax, 30, 54, 22, 6, "SincronizarFaseAtencionListener", C_LIST)
box(ax, 30, 40, 24, 7, "AtencionStateService\n::sincronizarFase()", C_SVC)
box(ax, 6, 40, 20, 6, "DTO\nCambioEstado", C_DTO)
box(ax, 57, 40, 22, 6, "event(AtencionEtapaCambiada)\nevent(AtencionEstatusActualizado)", C_EVENT)
box(ax, 6, 28, 26, 6, "RegistrarHistorial*\n(Etapa / Estatus)", C_LIST)

arrow(ax, 23, 71, 30, 71)
arrow(ax, 50, 71, 57, 71)
arrow(ax, 68, 68, 68, 60, color="#9D174D")
arrow(ax, 57, 57, 52, 57)
arrow(ax, 52, 57, 52, 54)  # bajada al servicio
arrow(ax, 42, 43.5, 42, 42)  # servicio->? (sin flecha clara)
arrow(ax, 26, 43, 22, 43, color="#047857")
arrow(ax, 54, 45, 57, 45, color="#047857")
arrow(ax, 6, 40, 6, 34, color="#047857")

label(ax, 26.5, 72.3, "Cotización pertenece a una Atención (id_atencion)")
label(ax, 54, 62.8, "saved() dispara evento")
label(ax, 41, 49.5, "evalúa etapas / cierre (ganada · perdida · reapertura)", color="#047857", fs=6.8)

# ── CICLO 3: ORDEN DE COMPRA ───────────────────────────
ax.text(3, 23.5, "3 · ORDEN DE COMPRA", fontsize=12, weight="bold", color="#1E40AF")

box(ax, 3, 15.5, 20, 6, "CotizacionController\n::update(estatus=aprobada)", C_CTRL)
box(ax, 30, 15.5, 22, 6, "event(CotizacionEstatusActualizado)", C_EVENT)
box(ax, 30, 3.5, 22, 6, "GenerarOrdenDesdeCotizacionListener", C_LIST)
box(ax, 57, 3.5, 20, 6, "OrdenCompra::create()", C_MODEL)
box(ax, 57, 15.5, 20, 6, "OrdenCompraObserver\n::saved()", C_OBS)
box(ax, 57, -6.5, 20, 6, "event(OrdenCompraGuardado)", C_EVENT)

arrow(ax, 23, 18.5, 30, 18.5)
arrow(ax, 41, 15.5, 41, 9.5, color="#9D174D")
arrow(ax, 52, 6.5, 57, 6.5)
arrow(ax, 67, 9.5, 67, 15.5, color="#9D174D")

# ── CICLO 4: PAGOS DEL CLIENTE (INGRESOS) ──────────────
ax.text(3, -10.5, "4 · PAGOS DEL CLIENTE (INGRESOS)", fontsize=12, weight="bold", color="#1E40AF")

box(ax, 3, -18.5, 20, 6, "PagoController\n::store()", C_CTRL)
box(ax, 30, -18.5, 22, 6, "PagoOrdenCompra::create()", C_MODEL)
box(ax, 57, -18.5, 22, 6, "PagoOrdenCompraObserver\n::saved()", C_OBS)
box(ax, 57, -31.5, 22, 6, "event(PagoOrdenCompraGuardado)", C_EVENT)
box(ax, 30, -31.5, 22, 6, "SincronizarEstadoFinancieroListener", C_LIST)
box(ax, 30, -43.5, 24, 7, "OrdenStateService\n::sincronizarFinanciero()", C_SVC)
box(ax, 57, -43.5, 20, 6, "DTO\nCambioEstado", C_DTO)

arrow(ax, 23, -15.5, 30, -15.5)
arrow(ax, 52, -15.5, 57, -15.5)
arrow(ax, 68, -18.5, 68, -31.5, color="#9D174D")
arrow(ax, 57, -34.5, 52, -34.5)
arrow(ax, 42, -40.0, 42, -43.5)
arrow(ax, 54, -40.0, 57, -40.0, color="#047857")

label(ax, 26.5, -14.2, "Pago se asigna a una/s orden/es de compra")
label(ax, 47, -37.8, "pendiente → parcial → pagado (según suma pagada)")

# ── CICLO 5: PAGOS A PROVEEDORES (EGRESOS) ─────────────
ax.text(3, -47.5, "5 · PAGOS A PROVEEDORES (EGRESOS)", fontsize=12, weight="bold", color="#1E40AF")

box(ax, 3, -55.5, 20, 6, "PagoProveedorController\n::store()", C_CTRL)
box(ax, 30, -55.5, 22, 6, "PagoProveedorCuenta::create()", C_MODEL)
box(ax, 57, -55.5, 22, 6, "PagoProveedorCuentaObserver\n::created()", C_OBS)
box(ax, 30, -68.5, 24, 7, "OrdenStateService\n::sincronizarEgreso()", C_SVC)
box(ax, 57, -68.5, 20, 6, "actualiza saldo\nCuentaPorPagar", C_DB)
box(ax, 3, -68.5, 24, 7, "OrdenStateService\n::sincronizarOperativo()", C_SVC)

arrow(ax, 23, -52.5, 30, -52.5)
arrow(ax, 52, -52.5, 57, -52.5)
arrow(ax, 45, -52.5, 45, -61.5, color="#047857")
arrow(ax, 30, -65, 26, -65, color="#047857")

label(ax, 52, -48.2, "pendiente → parcial → pagado (egreso)")
label(ax, 27, -61.8, "Operativo: pendiente / en_proceso / completada")

# ── CICLO 6: CUENTAS POR PAGAR ─────────────────────────
ax.text(3, -72.5, "6 · CUENTAS POR PAGAR", fontsize=12, weight="bold", color="#1E40AF")

box(ax, 3, -80.5, 22, 6, "event(OrdenCompraAprobada)", C_EVENT)
box(ax, 30, -80.5, 22, 6, "GenerarCuentasPorPagarListener", C_LIST)
box(ax, 57, -80.5, 22, 6, "CuentaPorPagar::create()\n(agrupado por proveedor)", C_MODEL)
box(ax, 57, -92.5, 22, 6, "Base de datos\n(MySQL)", C_DB)

arrow(ax, 25, -77.5, 30, -77.5)
arrow(ax, 52, -77.5, 57, -77.5)
arrow(ax, 68, -80.5, 68, -92.5, color="#334155")

# ── CAPA TRANSVERSAL ───────────────────────────────────
ax.text(3, -96.5, "CAPA TRANSVERSAL", fontsize=12, weight="bold", color="#6B21A8")
box(ax, 3, -103.5, 30, 6.5, "AuditLogger\n(eloquent.created/updated/deleted)", C_LIST)
box(ax, 36, -103.5, 30, 6.5, "LogroPersonalLogger\n(Atencion/Cotizacion/Orden)", C_LIST)
box(ax, 69, -103.5, 27, 6.5, "Sanctum + Spatie Permissions\n(role / permission / policies)", C_LIST)
box(ax, 3, -113.5, 30, 6.5, "Cache de catálogos\n(24h TTL)", C_DB)
box(ax, 36, -113.5, 30, 6.5, "Colas (queue:work)\nafter_commit=true", C_DB)
box(ax, 69, -113.5, 27, 6.5, "Reverb WebSockets\n(8080)", C_WS)

fig.savefig(r"c:\Users\Usuario\Desktop\Javier Suniaga\tag2\tag\docs\flujo_datos_tag2.png",
            dpi=130, bbox_inches="tight", facecolor="white")
print("Diagrama generado: docs/flujo_datos_tag2.png")
