from graphviz import Digraph

# Buat diagram flowchart
flow = Digraph("Flowchart_Tiket_Hotel", format="png")
flow.attr(rankdir="TB", size="8")

# Node style
flow.attr("node", shape="rectangle", style="rounded,filled", fillcolor="lightgrey")

# Mulai
flow.node("start", "Mulai")

# User actions
flow.node("login", "Login")
flow.node("register", "Register")
flow.node("pilih_hotel", "Pilih Hotel")
flow.node("pesan_kamar", "Pesan Kamar")
flow.node("cetak_struk", "Cetak Struk (Opsional)")
flow.node("riwayat", "Lihat Riwayat Pesanan")

# Admin actions
flow.node("kelola_hotel", "Kelola Hotel")
flow.node("tambah", "Tambah Hotel")
flow.node("update", "Update Hotel")
flow.node("hapus", "Hapus Hotel")
flow.node("lihat_user", "Lihat Semua User")
flow.node("lihat_transaksi", "Lihat Semua Transaksi")

# End
flow.node("end", "Selesai")

# Flow for User
flow.edge("start", "login")
flow.edge("login", "register", label="Belum punya akun?")
flow.edge("login", "pilih_hotel", label="User")
flow.edge("pilih_hotel", "pesan_kamar")
flow.edge("pesan_kamar", "cetak_struk", label="Opsional")
flow.edge("login", "riwayat", label="User")

# Flow for Admin
flow.edge("login", "kelola_hotel", label="Admin")
flow.edge("kelola_hotel", "tambah")
flow.edge("kelola_hotel", "update")
flow.edge("kelola_hotel", "hapus")
flow.edge("login", "lihat_user", label="Admin")
flow.edge("login", "lihat_transaksi", label="Admin")

# End connections
flow.edge("cetak_struk", "end")
flow.edge("riwayat", "end")
flow.edge("tambah", "end")
flow.edge("update", "end")
flow.edge("hapus", "end")
flow.edge("lihat_user", "end")
flow.edge("lihat_transaksi", "end")

# Render
output_path = "/mnt/data/flowchart_tiket_hotel"
flow.render(output_path, view=False)
output_path + ".png"
