class Contenido {
  final int id;
  final int subtemaId;
  final String titulo;
  final String cuerpo;
  final String tipoContenido; // 'texto', 'video', etc.

  Contenido({
    required this.id,
    required this.subtemaId,
    required this.titulo,
    required this.cuerpo,
    required this.tipoContenido,
  });

  factory Contenido.fromJson(Map<String, dynamic> json) {
    return Contenido(
      id: json['id'],
      subtemaId: int.parse(json['subtema_id'].toString()),
      titulo: json['titulo'],
      cuerpo: json['cuerpo'],
      tipoContenido: json['tipo_contenido'],
    );
  }
}